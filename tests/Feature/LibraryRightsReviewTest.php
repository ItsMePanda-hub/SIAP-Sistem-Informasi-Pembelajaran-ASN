<?php

namespace Tests\Feature;

use App\Enums\LearningLicenseType;
use App\Enums\LearningMaterialScope;
use App\Enums\LearningMaterialType;
use App\Enums\LearningPublicationStatus;
use App\Enums\LearningValidityStatus;
use App\Enums\LearningVerificationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialRightsLog;
use App\Models\LearningMaterialSource;
use App\Models\LibraryBookmark;
use App\Models\ReadingProgress;
use App\Models\User;
use App\Services\LibraryRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LibraryRightsReviewTest extends TestCase
{
    use RefreshDatabase;

    private function levelAndCategory(): array
    {
        $level = LearningLevel::factory()->create();
        $category = LearningCategory::factory()->create();
        return [$level, $category];
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function validCoreSourceData(array $over = []): array
    {
        return array_merge([
            'source_name' => 'Lembaga Administrasi Negara',
            'publisher' => 'LAN RI',
            'source_url' => 'https://lan.go.id/pedoman',
            'license_type' => 'cc-by-4.0',
            'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
            'rights_status' => 'approved',
            'rights_review_notes' => 'Hak cipta valid dan diverifikasi.',
        ], $over);
    }

    // 1. Classification Enums
    public function test_all_seven_rights_classifications_accepted(): void
    {
        $manager = $this->user('admin_library');
        [$level, $category] = $this->levelAndCategory();
        $material = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
        ]);

        foreach (LearningLicenseType::values() as $license) {
            $resp = $this->actingAs($manager)->put(route('library.source.update', $material), [
                'source_name' => 'Sumber Uji',
                'publisher' => 'Penerbit Uji',
                'source_url' => 'https://example.com/sumber',
                'license_type' => $license,
                'license_url' => 'https://example.com/license',
            ]);
            $resp->assertRedirect();
            $this->assertDatabaseHas('learning_material_sources', [
                'learning_material_id' => $material->id,
                'license_type' => $license,
            ]);
        }
    }

    // 2. Core requires license_type and evidence URL when required
    public function test_core_requires_license_and_evidence_url(): void
    {
        $uploader = $this->user('admin');
        $reviewer = $this->user('admin_library');
        [$level, $category] = $this->levelAndCategory();

        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'uploaded_by' => $uploader->id,
        ]);

        // Missing license_type
        $this->actingAs($reviewer)->put(route('library.source.update', $m), [
            'source_name' => 'LAN RI',
            'publisher' => 'LAN',
            'license_type' => '',
        ])->assertSessionHasErrors('license_type');

        // CC license without license_url
        $this->actingAs($reviewer)->put(route('library.source.update', $m), [
            'source_name' => 'LAN RI',
            'publisher' => 'LAN',
            'license_type' => 'cc-by-4.0',
            'license_url' => '',
        ])->assertSessionHasErrors('license_url');
    }

    // 3. Self-approval prevention
    public function test_uploader_cannot_self_approve_rights(): void
    {
        $uploader = $this->user('admin_library');
        [$level, $category] = $this->levelAndCategory();

        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'uploaded_by' => $uploader->id,
        ]);

        $payload = $this->validCoreSourceData(['rights_status' => 'approved']);

        // Uploader attempts to self-approve
        $resp = $this->actingAs($uploader)->put(route('library.source.update', $m), $payload);
        $resp->assertSessionHasErrors('rights_status');

        $this->assertDatabaseMissing('learning_material_sources', [
            'learning_material_id' => $m->id,
            'rights_status' => 'approved',
        ]);
    }

    // 4. Second reviewer manager can approve rights
    public function test_second_manager_can_approve_rights(): void
    {
        $uploader = $this->user('admin_library');
        $reviewer = $this->user('pemilik');
        [$level, $category] = $this->levelAndCategory();

        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'uploaded_by' => $uploader->id,
        ]);

        $payload = $this->validCoreSourceData(['rights_status' => 'approved']);

        $resp = $this->actingAs($reviewer)->put(route('library.source.update', $m), $payload);
        $resp->assertRedirect();

        $source = $m->fresh()->source;
        $this->assertSame('approved', $source->rights_status);
        $this->assertSame($reviewer->id, $source->rights_reviewed_by);
        $this->assertNotNull($source->rights_reviewed_at);
        $this->assertTrue($m->fresh()->hasApprovedRights());
    }

    // 5. Rights data change invalidates previous approval
    public function test_license_change_invalidates_stale_approval(): void
    {
        $uploader = $this->user('admin');
        $reviewer = $this->user('admin_library');
        [$level, $category] = $this->levelAndCategory();

        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'uploaded_by' => $uploader->id,
        ]);

        // Approved by reviewer
        $this->actingAs($reviewer)->put(route('library.source.update', $m), $this->validCoreSourceData(['rights_status' => 'approved']));
        $this->assertTrue($m->fresh()->source->isRightsApproved());

        // Uploader modifies license type to hak_cipta without reviewer approval
        $m->fresh()->source->update([
            'license_type' => 'hak_cipta',
            'license_url' => null,
        ]);

        $source = $m->fresh()->source;
        $this->assertSame('unreviewed', $source->rights_status);
        $this->assertNull($source->rights_reviewed_by);
        $this->assertNull($source->rights_reviewed_at);
        $this->assertFalse($m->fresh()->hasApprovedRights());
    }

    // 6. Core publication gate: non-redistributable licenses rejected for native/pdf
    public function test_core_native_pdf_rejects_non_redistributable_licenses(): void
    {
        $uploader = $this->user('admin');
        $reviewer = $this->user('admin_library');
        [$level, $category] = $this->levelAndCategory();

        foreach (['hak_cipta', 'cc-by-nc-sa', 'internal', 'tidak_diketahui'] as $badLic) {
            $m = LearningMaterial::factory()->core()->native()->create([
                'learning_level_id' => $level->id,
                'learning_category_id' => $category->id,
                'uploaded_by' => $uploader->id,
                'verification_status' => LearningVerificationStatus::Verified->value,
                'validity_status' => LearningValidityStatus::Active->value,
                'publication_status' => LearningPublicationStatus::Draft->value,
            ]);

            LearningMaterialSource::create([
                'learning_material_id' => $m->id,
                'source_name' => 'Sumber Uji',
                'publisher' => 'LAN RI',
                'license_type' => $badLic,
                'license_url' => 'https://example.com/license',
                'rights_status' => 'approved',
                'rights_reviewed_by' => $reviewer->id,
                'rights_reviewed_at' => now(),
            ]);

            $this->assertFalse($m->canCoreBePublished());

            try {
                $m->publish();
                $this->fail("Publish seharusnya gagal untuk lisensi {$badLic}");
            } catch (ValidationException $e) {
                $this->assertTrue(true);
            }
        }
    }

    // 7. Core publication succeeds for redistributable licenses
    public function test_core_publishes_with_redistributable_license(): void
    {
        $uploader = $this->user('admin');
        $reviewer = $this->user('admin_library');
        [$level, $category] = $this->levelAndCategory();

        foreach (['cc-by-4.0', 'cc-by-sa-4.0', 'izin_tertulis'] as $goodLic) {
            $m = LearningMaterial::factory()->core()->native()->create([
                'learning_level_id' => $level->id,
                'learning_category_id' => $category->id,
                'uploaded_by' => $uploader->id,
                'verification_status' => LearningVerificationStatus::Verified->value,
                'validity_status' => LearningValidityStatus::Active->value,
                'publication_status' => LearningPublicationStatus::Draft->value,
            ]);

            LearningMaterialSource::create([
                'learning_material_id' => $m->id,
                'source_name' => 'Sumber Resmi',
                'publisher' => 'LAN RI',
                'license_type' => $goodLic,
                'license_url' => 'https://example.com/proof',
                'rights_status' => 'approved',
                'rights_reviewed_by' => $reviewer->id,
                'rights_reviewed_at' => now(),
            ]);

            $this->assertTrue($m->canCoreBePublished());
            $m->publish();
            $this->assertTrue($m->fresh()->isPublished());
            $this->assertTrue($m->fresh()->isCurrent());
        }
    }

    // 8. Custom material compatibility
    public function test_custom_material_can_publish_without_core_redistribution_restriction(): void
    {
        $manager = $this->user('admin_library');
        [$level, $category] = $this->levelAndCategory();

        $custom = LearningMaterial::factory()->custom()->native()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => LearningPublicationStatus::Draft->value,
        ]);

        $custom->publish();
        $this->assertTrue($custom->fresh()->isPublished());
        $this->assertTrue($custom->fresh()->isCurrent());
    }

    // 9. Link-only material support
    public function test_link_only_material_creation_and_update(): void
    {
        $manager = $this->user('admin_library');
        [$level, $category] = $this->levelAndCategory();

        // Create link_only material (no content or pdf_file required)
        $resp = $this->actingAs($manager)->post(route('library.store'), [
            'title' => 'Peraturan ASN Link Only',
            'description' => 'Materi referensi eksternal.',
            'scope' => 'custom',
            'material_type' => 'link_only',
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'publication_status' => 'draft',
            'verification_status' => 'draft',
            'validity_status' => 'active',
        ]);
        $resp->assertRedirect(route('library.index'));

        $m = LearningMaterial::where('title', 'Peraturan ASN Link Only')->firstOrFail();
        $this->assertTrue($m->isLinkOnly());
        $this->assertNull($m->content);
        $this->assertNull($m->file_path);

        // Update source with source_url required
        $this->actingAs($manager)->put(route('library.source.update', $m), [
            'source_name' => 'JDIH LAN RI',
            'publisher' => 'Lembaga Administrasi Negara',
            'source_url' => '', // Empty fails
        ])->assertSessionHasErrors('source_url');

        $this->actingAs($manager)->put(route('library.source.update', $m), [
            'source_name' => 'JDIH LAN RI',
            'publisher' => 'Lembaga Administrasi Negara',
            'source_url' => 'https://jdih.lan.go.id/peraturan/123',
            'license_type' => 'hak_cipta',
        ])->assertRedirect();

        $this->assertSame('https://jdih.lan.go.id/peraturan/123', $m->fresh()->source->source_url);
    }

    // 10. Link-only PDF endpoint rejection
    public function test_link_only_pdf_route_returns_404(): void
    {
        $manager = $this->user('admin');
        [$level, $category] = $this->levelAndCategory();

        $m = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'material_type' => LearningMaterialType::LinkOnly->value,
            'publication_status' => LearningPublicationStatus::Published->value,
        ]);

        $this->actingAs($manager)->get(route('library.pdf', $m))->assertNotFound();
    }

    // 11. Link-only reader view
    public function test_link_only_reader_renders_external_reference_card(): void
    {
        $user = $this->user('pegawai');
        [$level, $category] = $this->levelAndCategory();

        $m = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'material_type' => LearningMaterialType::LinkOnly->value,
            'publication_status' => LearningPublicationStatus::Published->value,
            'published_at' => now(),
            'title' => 'PerLAN Digital Reference',
        ]);

        LearningMaterialSource::create([
            'learning_material_id' => $m->id,
            'source_name' => 'JDIH LAN',
            'publisher' => 'Lembaga Administrasi Negara',
            'source_url' => 'https://jdih.lan.go.id/dokumen/456',
            'license_type' => 'hak_cipta',
        ]);

        $resp = $this->actingAs($user)->get(route('library.read', $m));
        $resp->assertOk();
        $this->assertStringContainsString('Referensi Eksternal Resmi', $resp->getContent());
        $this->assertStringContainsString('https://jdih.lan.go.id/dokumen/456', $resp->getContent());
        $this->assertStringContainsString('Buka Tautan Sumber Resmi', $resp->getContent());
        $this->assertStringNotContainsString('<iframe', $resp->getContent());
    }

    // 12. Non-managers cannot edit or approve rights
    public function test_non_managers_cannot_edit_or_approve_rights(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
        ]);

        foreach (['atasan', 'pegawai'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user)->get(route('library.source.edit', $m))->assertForbidden();
            $this->actingAs($user)->put(route('library.source.update', $m), [
                'source_name' => 'Hacked',
                'publisher' => 'Hacked',
                'rights_status' => 'approved',
            ])->assertForbidden();
        }
    }

    // 13. Audit Trail: Multiple decisions append-only
    public function test_audit_trail_records_decisions_append_only(): void
    {
        $uploader = $this->user('admin');
        $reviewer1 = $this->user('admin_library');
        $reviewer2 = $this->user('pemilik');
        [$level, $category] = $this->levelAndCategory();

        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'uploaded_by' => $uploader->id,
        ]);

        // Review 1: Rejected
        $this->actingAs($reviewer1)->put(route('library.source.update', $m), $this->validCoreSourceData([
            'rights_status' => 'rejected',
            'rights_review_notes' => 'Bukti lisensi belum lengkap.',
        ]));

        $source = $m->fresh()->source;
        $this->assertSame(1, $source->rightsLogs()->count());
        $log1 = $source->rightsLogs()->first();
        $this->assertSame('rejected', $log1->decision);
        $this->assertSame($reviewer1->id, $log1->reviewer_user_id);
        $this->assertSame('Bukti lisensi belum lengkap.', $log1->notes);

        // Review 2: Approved by reviewer 2
        $this->actingAs($reviewer2)->put(route('library.source.update', $m), $this->validCoreSourceData([
            'rights_status' => 'approved',
            'rights_review_notes' => 'Lisensi CC BY 4.0 telah dikonfirmasi.',
        ]));

        $source->refresh();
        $this->assertSame(2, $source->rightsLogs()->count());
        $latestLog = $source->rightsLogs()->latest('id')->first();
        $this->assertSame('approved', $latestLog->decision);
        $this->assertSame($reviewer2->id, $latestLog->reviewer_user_id);
        $this->assertSame('Lisensi CC BY 4.0 telah dikonfirmasi.', $latestLog->notes);

        // Old log is intact
        $oldLog = $source->rightsLogs()->oldest('id')->first();
        $this->assertSame('rejected', $oldLog->decision);
        $this->assertSame($reviewer1->id, $oldLog->reviewer_user_id);
    }

    // 14. Access Path Hardening: Invalidated Core is blocked on all consumer routes
    public function test_invalidated_core_material_blocked_from_all_consumer_access_paths(): void
    {
        $uploader = $this->user('admin');
        $reviewer = $this->user('admin_library');
        $pegawai = $this->user('pegawai');
        [$level, $category] = $this->levelAndCategory();

        $m = LearningMaterial::factory()->core()->native()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'uploaded_by' => $uploader->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
            'title' => 'Core Access Hardening Title',
        ]);

        LearningMaterialSource::create([
            'learning_material_id' => $m->id,
            'source_name' => 'Sumber Resmi LAN',
            'publisher' => 'LAN RI',
            'source_url' => 'https://lan.go.id/pedoman',
            'license_type' => 'cc-by-4.0',
            'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
            'rights_status' => 'approved',
            'rights_reviewed_by' => $reviewer->id,
            'rights_reviewed_at' => now(),
        ]);

        $m->publish();
        $this->assertTrue($m->fresh()->isCurrent());

        // Consumer bookmarks it and reads it while valid
        LibraryBookmark::create(['user_id' => $pegawai->id, 'learning_material_id' => $m->id]);
        $this->actingAs($pegawai)->get(route('library.read', $m))->assertOk();
        $this->actingAs($pegawai)->get(route('library.index', ['q' => 'Hardening']))->assertSee('Core Access Hardening Title');

        // Now: Rights approval is invalidated due to license change to hak_cipta
        $m->fresh()->source->update([
            'license_type' => 'hak_cipta',
            'license_url' => null,
        ]);

        $this->assertFalse($m->fresh()->isCurrent());

        // Path 1: Index & Search Catalog
        $this->actingAs($pegawai)->get(route('library.index', ['q' => 'Hardening']))->assertDontSee('Core Access Hardening Title');
        $this->actingAs($pegawai)->get(route('library.index'))->assertDontSee('Core Access Hardening Title');

        // Path 2: Recommendations
        $recs = (new LibraryRecommendationService())->getRecommendations($pegawai);
        $this->assertNotContains($m->id, $recs->pluck('material.id')->all());

        // Path 3: Direct Reader Route
        $this->actingAs($pegawai)->get(route('library.read', $m))->assertForbidden();

        // Path 4: Bookmarks Page
        $bookmarkResp = $this->actingAs($pegawai)->get(route('library.bookmarks'));
        $bookmarkResp->assertOk();
        $this->assertStringContainsString('Tidak tersedia', $bookmarkResp->getContent());
        $this->assertStringNotContainsString(route('library.read', $m), $bookmarkResp->getContent());

        // Path 5: PDF Route
        $this->actingAs($pegawai)->get(route('library.pdf', $m))->assertForbidden();
    }

    // 15. Rights review notes and evidence not leaked to consumers
    public function test_rights_review_notes_not_leaked_to_consumer(): void
    {
        $uploader = $this->user('admin');
        $reviewer = $this->user('admin_library');
        $pegawai = $this->user('pegawai');
        [$level, $category] = $this->levelAndCategory();

        $m = LearningMaterial::factory()->core()->native()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'uploaded_by' => $uploader->id,
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
            'publication_status' => LearningPublicationStatus::Draft->value,
            'title' => 'Privacy Check Title',
        ]);

        LearningMaterialSource::create([
            'learning_material_id' => $m->id,
            'source_name' => 'Sumber Resmi LAN',
            'publisher' => 'LAN RI',
            'license_type' => 'cc-by-4.0',
            'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
            'rights_status' => 'approved',
            'rights_reviewed_by' => $reviewer->id,
            'rights_reviewed_at' => now(),
            'rights_review_notes' => 'INTERNAL_SECRET_LEGAL_NOTE_12345',
        ]);

        $m->publish();

        // Consumer opens index & reader
        $indexHtml = $this->actingAs($pegawai)->get(route('library.index'))->getContent();
        $readHtml = $this->actingAs($pegawai)->get(route('library.read', $m))->getContent();

        $this->assertStringNotContainsString('INTERNAL_SECRET_LEGAL_NOTE_12345', $indexHtml);
        $this->assertStringNotContainsString('INTERNAL_SECRET_LEGAL_NOTE_12345', $readHtml);
    }

    // 16. Model-level guard: Core approved requires license evidence
    public function test_model_guard_rejects_core_approved_without_license_evidence(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $uploader = $this->user('admin');
        $reviewer = $this->user('admin_library');
        $m = LearningMaterial::factory()->core()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'uploaded_by' => $uploader->id,
        ]);

        $this->expectException(ValidationException::class);
        LearningMaterialSource::create([
            'learning_material_id' => $m->id,
            'source_name' => 'Sumber Uji',
            'publisher' => 'Penerbit Uji',
            'license_type' => 'cc-by-4.0',
            'license_url' => null,
            'rights_status' => 'approved',
            'rights_reviewed_by' => $reviewer->id,
            'rights_reviewed_at' => now(),
        ]);
    }

    // 17. Model-level guard: LinkOnly requires source_url
    public function test_model_guard_rejects_linkonly_without_source_url(): void
    {
        [$level, $category] = $this->levelAndCategory();
        $m = LearningMaterial::factory()->custom()->create([
            'learning_level_id' => $level->id,
            'learning_category_id' => $category->id,
            'material_type' => LearningMaterialType::LinkOnly->value,
        ]);

        $this->expectException(ValidationException::class);
        LearningMaterialSource::create([
            'learning_material_id' => $m->id,
            'source_name' => 'Sumber Uji',
            'publisher' => 'Penerbit Uji',
            'source_url' => null,
            'license_type' => 'hak_cipta',
        ]);
    }

    // 18. scopeCurrent parity with isCurrent (>=8 combos)
    public function test_scope_current_parity_with_is_current(): void
    {
        $reviewer = $this->user('admin_library');
        $uploader = $this->user('admin');
        [$level, $category] = $this->levelAndCategory();

        $make = function (array $matOver, array $srcOver) use ($level, $category, $uploader, $reviewer) {
            $published = $matOver['publication_status'] ?? LearningPublicationStatus::Published->value;
            $over = array_merge([
                'learning_level_id' => $level->id,
                'learning_category_id' => $category->id,
                'publication_status' => LearningPublicationStatus::Draft->value,
                'verification_status' => LearningVerificationStatus::Verified->value,
                'validity_status' => LearningValidityStatus::Active->value,
                'uploaded_by' => $uploader->id,
            ], $matOver);
            $over['publication_status'] = LearningPublicationStatus::Draft->value;
            $m = LearningMaterial::factory()->create($over);
            if ($srcOver !== []) {
                try {
                    LearningMaterialSource::create(array_merge(['learning_material_id' => $m->id], $srcOver));
                } catch (\Illuminate\Validation\ValidationException $e) {
                    \Illuminate\Support\Facades\DB::table('learning_material_sources')->insert(
                        array_merge(['learning_material_id' => $m->id], $srcOver)
                    );
                }
            }
            if ($published === LearningPublicationStatus::Published->value) {
                try { $m->publish(); } catch (\Illuminate\Validation\ValidationException $e) {}
            }
            return $m->fresh();
        };

        $srcOk = [
            'source_name' => 'S', 'publisher' => 'P',
            'license_type' => 'cc-by-4.0', 'license_url' => 'https://example.com/lic',
            'rights_status' => 'approved', 'rights_reviewed_by' => $reviewer->id, 'rights_reviewed_at' => now(),
        ];

        $cases = [];
        $cases[] = $make(['scope' => LearningMaterialScope::Core->value, 'material_type' => 'native', 'content' => 'x'], $srcOk);
        $cases[] = $make(['scope' => LearningMaterialScope::Core->value, 'material_type' => 'pdf', 'file_path' => 'library-pdfs/x.pdf'], $srcOk);
        $cases[] = $make(['scope' => LearningMaterialScope::Core->value, 'material_type' => 'native', 'content' => 'x'],
            array_merge($srcOk, ['license_type' => 'hak_cipta', 'license_url' => null]));
        $cases[] = $make(['scope' => LearningMaterialScope::Core->value, 'material_type' => 'native', 'content' => 'x'],
            array_merge($srcOk, ['license_type' => 'cc-by-4.0', 'license_url' => null]));
        $cases[] = $make(['scope' => LearningMaterialScope::Core->value, 'material_type' => 'link_only'],
            array_merge($srcOk, ['license_type' => 'hak_cipta', 'license_url' => null, 'source_url' => 'https://example.com/s']));
        $cases[] = $make(['scope' => LearningMaterialScope::Core->value, 'material_type' => 'link_only'],
            array_merge($srcOk, ['license_type' => 'hak_cipta', 'source_url' => null]));
        $cases[] = $make(['scope' => LearningMaterialScope::Core->value, 'material_type' => 'native', 'content' => 'x'],
            array_merge($srcOk, ['license_type' => 'tidak_diketahui', 'license_url' => null]));
        $cases[] = $make(['scope' => LearningMaterialScope::Core->value, 'material_type' => 'native', 'content' => 'x'],
            array_merge($srcOk, ['rights_reviewed_by' => $uploader->id]));

        $this->assertCount(8, $cases);
        foreach ($cases as $m) {
            $expected = $m->isCurrent();
            $inScope = LearningMaterial::current()->where('id', $m->id)->exists();
            $this->assertSame($expected, $inScope, "parity gagal untuk material {$m->id}");
        }
    }
}
