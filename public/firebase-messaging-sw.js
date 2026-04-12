// firebase-messaging-sw.js
// Place this file in: public/firebase-messaging-sw.js
// It MUST be served from the root of the site so it has the correct scope.

importScripts(
    "https://www.gstatic.com/firebasejs/12.11.0/firebase-app-compat.js",
);
importScripts(
    "https://www.gstatic.com/firebasejs/12.11.0/firebase-messaging-compat.js",
);

// These values are replaced at runtime via a GET param — see layouts/app.blade.php
self.addEventListener("message", function (event) {
    if (event.data && event.data.type === "FIREBASE_CONFIG") {
        firebase.initializeApp(event.data.config);

        const messaging = firebase.messaging();

        messaging.onBackgroundMessage(function (payload) {
            const { title, body } = payload.notification ?? {};
            self.registration.showNotification(title ?? "Notification", {
                body: body ?? "",
                icon: "/assets/img/logo.png",
                badge: "/assets/img/badge.png",
                data: payload.data ?? {},
            });
        });
    }
});

// Click on notification → navigate to the app
self.addEventListener("notificationclick", function (event) {
    event.notification.close();
    const url = event.notification.data?.link ?? "/";
    event.waitUntil(clients.openWindow(url));
});
