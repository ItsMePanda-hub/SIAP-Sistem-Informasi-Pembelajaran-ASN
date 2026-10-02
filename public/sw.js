self.addEventListener('push', (event) => {
  let data = { title: 'SIAP', body: '', url: '/notifications' };
  try {
    if (event.data) {
      const json = event.data.json();
      data = {
        title: json.title || 'SIAP',
        body: json.body || json.message || '',
        url: json.url || '/notifications',
        type: json.type || 'notification',
      };
    }
  } catch (e) {
    try { data.body = event.data ? event.data.text() : ''; } catch (e2) {}
  }
  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: '/favicon.ico',
      data,
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/notifications';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
      for (const c of list) {
        if ('focus' in c) { c.navigate(url); return c.focus(); }
      }
      if (clients.openWindow) return clients.openWindow(url);
    })
  );
});
