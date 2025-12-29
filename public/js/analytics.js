/**
 * Kinvoice Analytics - Lean, first-party event tracking
 *
 * Usage:
 *   window.KinvoiceAnalytics.track('event_name', { key: 'value' });
 */
(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        apiEndpoint: '/api/v1/analytics/events',
        batchSize: 10,
        batchTimeout: 5000, // 5 seconds
        anonymousIdKey: 'ka_anonymous_id',
        sessionIdKey: 'ka_session_id',
        sessionTimeout: 30 * 60 * 1000, // 30 minutes
    };

    // Event queue
    let eventQueue = [];
    let batchTimer = null;

    /**
     * Generate a UUID v4
     */
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    /**
     * Get or create anonymous ID (persists across sessions)
     */
    function getAnonymousId() {
        try {
            let anonymousId = localStorage.getItem(CONFIG.anonymousIdKey);
            if (!anonymousId) {
                anonymousId = generateUUID();
                localStorage.setItem(CONFIG.anonymousIdKey, anonymousId);
            }
            return anonymousId;
        } catch (e) {
            // localStorage not available, generate temporary ID
            return generateUUID();
        }
    }

    /**
     * Get or create session ID (expires after 30 minutes of inactivity)
     */
    function getSessionId() {
        try {
            const now = Date.now();
            const sessionData = sessionStorage.getItem(CONFIG.sessionIdKey);

            if (sessionData) {
                const { id, lastActivity } = JSON.parse(sessionData);

                // Check if session has expired
                if (now - lastActivity < CONFIG.sessionTimeout) {
                    // Update last activity time
                    sessionStorage.setItem(CONFIG.sessionIdKey, JSON.stringify({
                        id,
                        lastActivity: now
                    }));
                    return id;
                }
            }

            // Create new session
            const newSessionId = generateUUID();
            sessionStorage.setItem(CONFIG.sessionIdKey, JSON.stringify({
                id: newSessionId,
                lastActivity: now
            }));
            return newSessionId;
        } catch (e) {
            // sessionStorage not available, generate temporary ID
            return generateUUID();
        }
    }

    /**
     * Extract UTM parameters from URL
     */
    function getUtmParams() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const utm = {};

            ['source', 'medium', 'campaign', 'term', 'content'].forEach(param => {
                const value = urlParams.get(`utm_${param}`);
                if (value) {
                    utm[param] = value;
                }
            });

            return Object.keys(utm).length > 0 ? utm : null;
        } catch (e) {
            return null;
        }
    }

    /**
     * Send events to the API
     */
    function sendEvents(events) {
        if (events.length === 0) return;

        const payload = { events };

        // Try to use sendBeacon for non-blocking request
        if (navigator.sendBeacon) {
            const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
            const sent = navigator.sendBeacon(CONFIG.apiEndpoint, blob);

            if (sent) {
                return;
            }
        }

        // Fallback to fetch if sendBeacon fails or is not available
        fetch(CONFIG.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
            keepalive: true,
        }).catch(function(error) {
            // Silently fail - analytics shouldn't break the user experience
            console.debug('Analytics tracking failed:', error);
        });
    }

    /**
     * Flush the event queue
     */
    function flushQueue() {
        if (eventQueue.length === 0) return;

        const eventsToSend = eventQueue.splice(0, CONFIG.batchSize);
        sendEvents(eventsToSend);

        // Clear the batch timer
        if (batchTimer) {
            clearTimeout(batchTimer);
            batchTimer = null;
        }

        // If there are still events in the queue, schedule next batch
        if (eventQueue.length > 0) {
            batchTimer = setTimeout(flushQueue, CONFIG.batchTimeout);
        }
    }

    /**
     * Track an analytics event
     *
     * @param {string} name - Event name (lowercase, underscores only)
     * @param {object} properties - Optional event properties (no PII)
     */
    function track(name, properties) {
        // Validate event name
        if (!name || typeof name !== 'string') {
            console.warn('Analytics: Invalid event name');
            return;
        }

        // Sanitize event name
        name = name.toLowerCase().replace(/[^a-z0-9_]/g, '_');

        // Build event object
        const event = {
            name: name,
            ts: Math.floor(Date.now() / 1000), // Unix timestamp
            path: window.location.pathname,
            referrer: document.referrer || null,
            session_id: getSessionId(),
            anonymous_id: getAnonymousId(),
        };

        // Add UTM parameters if present
        const utm = getUtmParams();
        if (utm) {
            event.utm = utm;
        }

        // Add custom properties (sanitize to prevent PII)
        if (properties && typeof properties === 'object') {
            event.properties = properties;
        }

        // Add to queue
        eventQueue.push(event);

        // Flush queue if batch size reached
        if (eventQueue.length >= CONFIG.batchSize) {
            flushQueue();
        } else {
            // Schedule batch flush if not already scheduled
            if (!batchTimer) {
                batchTimer = setTimeout(flushQueue, CONFIG.batchTimeout);
            }
        }
    }

    /**
     * Track page view
     */
    function trackPageView() {
        const pageName = window.location.pathname === '/'
            ? 'landing_page_viewed'
            : window.location.pathname.replace(/\//g, '_').replace(/^_/, '') + '_viewed';

        track(pageName);
    }

    /**
     * Flush queue before page unload
     */
    function onBeforeUnload() {
        if (eventQueue.length > 0) {
            const eventsToSend = eventQueue.splice(0);
            sendEvents(eventsToSend);
        }
    }

    // Auto-flush on page unload
    window.addEventListener('beforeunload', onBeforeUnload);
    window.addEventListener('pagehide', onBeforeUnload);

    // Expose public API
    window.KinvoiceAnalytics = {
        track: track,
        trackPageView: trackPageView,
        flush: flushQueue,
    };

    // Auto-track page view on load (optional - can be disabled)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', trackPageView);
    } else {
        trackPageView();
    }
})();
