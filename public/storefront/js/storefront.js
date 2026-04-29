const config = window.storefrontConfig || {};
const cartDrawer = document.querySelector('.sf-cart-drawer');
const cartBackdrop = document.querySelector('.sf-drawer-backdrop');
const cartDrawerContent = document.querySelector('.js-cart-drawer');
const cartCountEls = document.querySelectorAll('.js-cart-count');
const locationLabelEls = document.querySelectorAll('.js-location-label');
const locationModalEl = document.getElementById('locationModal');
const locationModal = locationModalEl ? new bootstrap.Modal(locationModalEl) : null;
const locationForm = document.querySelector('.js-location-form');
const countrySelect = document.querySelector('.js-country-select');
const citySelect = document.querySelector('.js-city-select');
const zoneSelect = document.querySelector('.js-zone-select');
const guestCartKey = 'expressbazar.guestCart';
const legacyGuestCartKey = 'guest_cart';
window.storefrontAjaxFilters = true;

function uiMessage(key, fallback) {
    return config.uiMessages?.[key] || fallback;
}

function showError(message) {
    const text = message || uiMessage('api_error', 'Something went wrong. Please try again');
    let errorBox = document.getElementById('error-box');

    if (!errorBox) {
        errorBox = document.createElement('div');
        errorBox.id = 'error-box';
        errorBox.className = 'alert alert-danger border-0 shadow-sm rounded-4 mb-3';
        const container = document.querySelector('main.sf-page') || document.body;
        container.prepend(errorBox);
    }

    errorBox.textContent = text;
    errorBox.style.display = 'block';
}

function getGuestCartState() {
    try {
        const raw = localStorage.getItem(guestCartKey) || localStorage.getItem(legacyGuestCartKey);
        return raw ? JSON.parse(raw) : [];
    } catch (error) {
        return [];
    }
}

function setGuestCartState(state) {
    try {
        localStorage.setItem(guestCartKey, JSON.stringify(Array.isArray(state) ? state : []));
    } catch (error) {
        // Ignore storage errors.
    }
}

function clearGuestCartState() {
    try {
        localStorage.removeItem(guestCartKey);
        localStorage.removeItem(legacyGuestCartKey);
    } catch (error) {
        // Ignore storage errors.
    }
}

function shouldMirrorGuestCart() {
    return config.currentUserRole !== 'customer';
}

function normalizeCartState(state) {
    if (!Array.isArray(state)) {
        return [];
    }

    return state
        .map((item) => ({
            product_id: Number(item.product_id || 0),
            quantity: Number(item.quantity || 0),
        }))
        .filter((item) => item.product_id > 0 && item.quantity > 0)
        .sort((left, right) => left.product_id - right.product_id);
}

function cartStatesEqual(left, right) {
    const a = normalizeCartState(left);
    const b = normalizeCartState(right);

    if (a.length !== b.length) {
        return false;
    }

    return a.every((item, index) => {
        return item.product_id === b[index].product_id && item.quantity === b[index].quantity;
    });
}

function openCart() {
    cartDrawer?.classList.add('open');
    cartBackdrop?.classList.add('show');
}

function closeCart() {
    cartDrawer?.classList.remove('open');
    cartBackdrop?.classList.remove('show');
}

function updateCartUi(payload = {}) {
    if (typeof payload.cartCount !== 'undefined') {
        cartCountEls.forEach((el) => {
            el.textContent = String(payload.cartCount);
        });
        document.body.dataset.cartCount = String(payload.cartCount);
    }

    if (payload.drawerHtml && cartDrawerContent) {
        cartDrawerContent.innerHTML = payload.drawerHtml;
    }

    if (payload.cartState && shouldMirrorGuestCart()) {
        setGuestCartState(payload.cartState);
    }

    if (payload.locationLabel) {
        locationLabelEls.forEach((el) => {
            el.textContent = payload.locationLabel;
        });
    }

    updateCartPage(payload);
}

function cartUrl(template, productId) {
    return String(template || '').replace('__ID__', String(productId));
}

function formatCartAmount(value) {
    const amount = Number(value || 0);

    return `\u20b9${Math.round(amount).toLocaleString('en-IN')}`;
}

function updateCartPage(payload = {}) {
    const cartItemsEl = document.querySelector('[data-cart-items]');
    const summaryEls = document.querySelectorAll('.sf-cart-summary .d-flex strong');

    if (payload.cartItem && cartItemsEl) {
        const productId = String(payload.cartItem.productId);
        const row = cartItemsEl.querySelector(`[data-cart-row][data-product="${productId}"]`);

        if (row) {
            const quantity = Number(payload.cartItem.quantity || 0);
            const quantityLabel = row.querySelector('.flex-grow-1 .text-secondary.small:last-child');
            const stepperValue = row.querySelector('[data-cart-stepper-value]');
            const subtotal = row.querySelector('.text-end > .fw-semibold');

            if (quantityLabel) {
                quantityLabel.textContent = `${quantity} x ${formatCartAmount(payload.cartItem.unitPrice)}`;
            }

            if (stepperValue) {
                stepperValue.textContent = String(quantity);
            }

            if (subtotal) {
                subtotal.textContent = formatCartAmount(payload.cartItem.subtotal);
            }
        }
    }

    if (payload.removedProductId && cartItemsEl) {
        const row = cartItemsEl.querySelector(`[data-cart-row][data-product="${payload.removedProductId}"]`);
        row?.remove();
    }

    if (cartItemsEl && Number(payload.cartCount || 0) === 0) {
        cartItemsEl.innerHTML = `<div class="sf-empty-state">${escapeHtml(uiMessage('empty_cart', 'Your cart is empty'))}</div>`;
    }

    if (payload.cartTotals && summaryEls.length >= 3) {
        summaryEls[0].textContent = formatCartAmount(payload.cartTotals.itemTotal);
        summaryEls[1].textContent = formatCartAmount(payload.cartTotals.delivery);
        summaryEls[2].textContent = formatCartAmount(payload.cartTotals.grandTotal);
    }
}

function showLocationModal() {
    locationModal?.show();
}

async function fetchJson(url) {
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    });
    const payload = await response.json();

    if (!response.ok || payload?.error) {
        throw new Error(payload?.message || uiMessage('api_error', 'Something went wrong. Please try again'));
    }

    return payload;
}

function updateProductList(html) {
    const productList = document.querySelector('.js-product-list');
    if (productList && typeof html === 'string') {
        productList.innerHTML = html;
    }
}

function notificationMessage(notification) {
    return notification?.data?.message || notification?.data?.title || 'Notification';
}

function renderNotifications(data) {
    const container = document.getElementById('notification-list');
    const countEl = document.getElementById('notification-count');

    if (!container) {
        return;
    }

    const notifications = Array.isArray(data) ? data : [];
    const unreadCount = notifications.filter((notification) => !notification.read_at).length;

    if (countEl) {
        countEl.textContent = String(unreadCount);
        countEl.classList.toggle('d-none', unreadCount < 1);
    }

    if (notifications.length === 0) {
        container.innerHTML = '<div class="dropdown-item-text small text-secondary px-2 py-2">No notifications</div>';
        return;
    }

    container.innerHTML = notifications
        .map((notification) => {
            const unreadClass = notification.read_at ? '' : 'fw-semibold';
            const message = escapeHtml(notificationMessage(notification));
            const id = escapeHtml(notification.id);

            return `
                <div class="dropdown-item-text small text-secondary px-2 py-2 ${unreadClass}">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <span>${message}</span>
                        <button type="button" class="btn btn-sm btn-light py-0 px-1 js-notification-read" data-notification-id="${id}" aria-label="Mark notification as read">
                            <i class="ti ti-check"></i>
                        </button>
                    </div>
                </div>
            `;
        })
        .join('');
}

async function loadNotifications() {
    if (!config.notificationsUrl) {
        return;
    }

    try {
        const notifications = await fetchJson(config.notificationsUrl);
        renderNotifications(notifications);
    } catch (error) {
        showError(error.message);
    }
}

async function markRead(id) {
    if (!id || !config.notificationReadUrlTemplate) {
        return;
    }

    const url = String(config.notificationReadUrlTemplate).replace('__ID__', encodeURIComponent(id));

    try {
        const { response, payload } = await sendCartAction(url, {
            method: 'POST',
            body: new FormData(),
        });

        if (!response.ok || payload?.error) {
            showError(payload?.message || uiMessage('api_error', 'Something went wrong. Please try again'));
            return;
        }

        loadNotifications();
    } catch (error) {
        showError(error.message);
    }
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

async function loadFilteredProducts(form) {
    const productList = document.querySelector('.js-product-list');
    if (!productList || !form) {
        form?.submit();
        return;
    }

    const url = new URL(form.action || window.location.href, window.location.origin);
    const formData = new FormData(form);
    formData.forEach((value, key) => {
        if (value !== '') {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
    });

    let response;
    let payload;

    try {
        response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });
        payload = await response.json();
    } catch (error) {
        updateProductList(`<div class="sf-empty-state">${escapeHtml(uiMessage('api_error', 'Something went wrong. Please try again'))}</div>`);
        showError(uiMessage('api_error', 'Something went wrong. Please try again'));
        return;
    }

    if (!response.ok || payload?.error) {
        const message = payload?.message || uiMessage('api_error', 'Something went wrong. Please try again');
        updateProductList(`<div class="sf-empty-state">${escapeHtml(message)}</div>`);
        showError(message);
        return;
    }

    if (payload.require_location) {
        updateProductList(`<div class="sf-empty-state">${escapeHtml(payload.message || 'Enter your delivery location to see exact availability')}</div>`);
        showLocationModal();
        return;
    }

    updateProductList(payload.html);
    window.history.replaceState({}, '', url);
}

async function mergeGuestCartIfNeeded() {
    if (config.currentUserRole !== 'customer') {
        return;
    }

    const guestCart = getGuestCartState();
    if (!Array.isArray(guestCart) || guestCart.length === 0) {
        return;
    }

    if (cartStatesEqual(guestCart, config.initialCartState || [])) {
        clearGuestCartState();
        return;
    }

    const { response, payload } = await sendCartAction(config.cartMergeUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ items: guestCart }),
    });

    if (response.ok) {
        updateCartUi(payload);
        clearGuestCartState();
    }
}

async function loadCities(countryId, selectedCityId = null) {
    if (!countryId || !citySelect) {
        return;
    }

    let cities = [];

    try {
        const url = new URL(config.locationCitiesUrl, window.location.origin);
        url.searchParams.set('country_id', countryId);
        const payload = await fetchJson(url);
        cities = payload.cities || [];
    } catch (error) {
        showError(error.message);
    }

    citySelect.innerHTML = '<option value="">Choose city</option>';
    cities.forEach((city) => {
        const option = document.createElement('option');
        option.value = city.id;
        option.textContent = city.city_name;
        if (String(city.id) === String(selectedCityId)) {
            option.selected = true;
        }
        citySelect.appendChild(option);
    });
}

async function loadZones(cityId, selectedZoneId = null) {
    if (!cityId || !zoneSelect) {
        return;
    }

    let zones = [];

    try {
        const url = new URL(config.locationZonesUrl, window.location.origin);
        url.searchParams.set('city_id', cityId);
        const payload = await fetchJson(url);
        zones = payload.zones || [];
    } catch (error) {
        showError(error.message);
    }

    zoneSelect.innerHTML = '<option value="">Optional exact zone</option>';
    zones.forEach((zone) => {
        const option = document.createElement('option');
        option.value = zone.id;
        option.textContent = `${zone.zone_name}${zone.zone_code ? ` (${zone.zone_code})` : ''}`;
        if (String(zone.id) === String(selectedZoneId)) {
            option.selected = true;
        }
        zoneSelect.appendChild(option);
    });
}

async function sendCartAction(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': config.csrfToken || '',
                ...(options.headers || {}),
            },
            ...options,
        });

        let payload = {};
        try {
            payload = await response.json();
        } catch (error) {
            payload = {};
        }

        return { response, payload };
    } catch (error) {
        return {
            response: { ok: false },
            payload: {
                error: true,
                message: uiMessage('api_error', 'Something went wrong. Please try again'),
            },
        };
    }
}

function syncLocationInputs(initialLocation) {
    if (!initialLocation || !countrySelect) {
        return;
    }

    countrySelect.value = initialLocation.country_id || '';
    loadCities(initialLocation.country_id, initialLocation.city_id).then(() => {
        if (citySelect) {
            citySelect.value = initialLocation.city_id || '';
        }
        if (initialLocation.city_id) {
            loadZones(initialLocation.city_id, initialLocation.zone_id).then(() => {
                if (zoneSelect) {
                    zoneSelect.value = initialLocation.zone_id || '';
                }
            });
        }
    });
}

document.addEventListener('click', async (event) => {
    const railButton = event.target.closest('.js-rail-scroll');
    if (railButton) {
        event.preventDefault();
        const rail = railButton.closest('.sf-rail-wrap')?.querySelector('.sf-product-rail');
        const direction = Number(railButton.dataset.direction || 1);

        if (rail) {
            rail.scrollBy({
                left: direction * Math.max(rail.clientWidth * 0.8, 220),
                behavior: 'smooth',
            });
        }
        return;
    }

    const openCartTrigger = event.target.closest('.js-open-cart');
    if (openCartTrigger) {
        event.preventDefault();
        openCart();
        return;
    }

    const closeCartTrigger = event.target.closest('.js-close-cart');
    if (closeCartTrigger) {
        event.preventDefault();
        closeCart();
        return;
    }

    const clearCartTrigger = event.target.closest('.js-clear-cart');
    if (clearCartTrigger) {
        event.preventDefault();
        const { response, payload } = await sendCartAction(config.cartClearUrl, {
            method: 'POST',
            body: new FormData(),
        });

        if (response.ok) {
            updateCartUi(payload);
        }
        return;
    }

    const notificationReadButton = event.target.closest('.js-notification-read');
    if (notificationReadButton) {
        event.preventDefault();
        markRead(notificationReadButton.dataset.notificationId);
        return;
    }

    const addForm = event.target.closest('.js-add-to-cart');
    if (addForm) {
        event.preventDefault();
        const formData = new FormData(addForm);
        const { response, payload } = await sendCartAction(addForm.action, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            const locationMessage = payload?.errors?.location?.[0] || payload?.message;
            if (locationMessage && locationMessage.toLowerCase().includes('location')) {
                showLocationModal();
            } else if (payload?.message) {
                showError(payload.message);
            } else {
                showError(uiMessage('api_error', 'Something went wrong. Please try again'));
            }
            return;
        }

        updateCartUi(payload);
        openCart();
        return;
    }

    const adjustButton = event.target.closest('.js-cart-adjust');
    if (adjustButton) {
        event.preventDefault();
        const productId = adjustButton.dataset.product;
        const delta = adjustButton.dataset.delta;
        const url = cartUrl(config.cartUpdateUrlTemplate, productId);
        const formData = new FormData();
        formData.append('delta', delta);
        formData.append('_method', 'PATCH');
        const { response, payload } = await sendCartAction(url, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            showError(payload?.message || uiMessage('api_error', 'Something went wrong. Please try again'));
            return;
        }

        updateCartUi(payload);
        if (payload.cartCount === 0) {
            closeCart();
        }
        return;
    }

    const removeForm = event.target.closest('.js-cart-remove');
    if (removeForm) {
        event.preventDefault();
        const { response, payload } = await sendCartAction(removeForm.action, {
            method: 'POST',
            body: new FormData(removeForm),
        });

        if (response.ok) {
            updateCartUi(payload);
        }
    }
});

cartBackdrop?.addEventListener('click', closeCart);

document.addEventListener('submit', async (event) => {
    const loginForm = event.target.closest('.js-login-form');
    if (loginForm) {
        const guestCartInput = loginForm.querySelector('.js-guest-cart-input');
        if (guestCartInput) {
            guestCartInput.value = JSON.stringify(normalizeCartState(getGuestCartState()));
        }
        return;
    }

    const logoutForm = event.target.closest('.js-logout-form');
    if (logoutForm) {
        event.preventDefault();

        const existingGuestCart = getGuestCartState();
        const cartToPreserve = existingGuestCart.length > 0
            ? existingGuestCart
            : normalizeCartState(config.initialCartState || []);

        if (cartToPreserve.length > 0) {
            setGuestCartState(cartToPreserve);
        }

        try {
            await fetch(logoutForm.action || config.logoutUrl || '/logout', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                    'X-CSRF-TOKEN': config.csrfToken || '',
                },
                body: new FormData(logoutForm),
            });
        } catch (error) {
            if (cartToPreserve.length > 0) {
                setGuestCartState(cartToPreserve);
            }
        }

        window.location.href = '/';
        return;
    }

    const form = event.target.closest('.js-location-form');
    if (!form) {
        return;
    }

    event.preventDefault();
    const formData = new FormData(form);
    const { response, payload } = await sendCartAction(form.action, {
        method: 'POST',
        body: formData,
    });

    if (!response.ok) {
        if (payload?.needs_confirmation) {
            if (confirm(payload.message)) {
                formData.set('force_clear', '1');
                const retry = await sendCartAction(form.action, {
                    method: 'POST',
                    body: formData,
                });
                if (retry.response.ok) {
                    updateCartUi(retry.payload);
                    locationModal?.hide();
                    window.location.reload();
                }
            }
        } else if (payload?.message) {
            showError(payload.message);
        } else if (payload?.errors?.postcode?.[0]) {
            showError(payload.errors.postcode[0]);
        } else {
            showError(uiMessage('api_error', 'Something went wrong. Please try again'));
        }
        return;
    }

    updateCartUi(payload);
    locationModal?.hide();
    window.location.reload();
});

countrySelect?.addEventListener('change', async () => {
    const countryId = countrySelect.value;
    if (!countryId) {
        if (citySelect) {
            citySelect.innerHTML = '<option value="">Choose city</option>';
        }
        if (zoneSelect) {
            zoneSelect.innerHTML = '<option value="">Optional exact zone</option>';
        }
        return;
    }
    await loadCities(countryId);
    if (zoneSelect) {
        zoneSelect.innerHTML = '<option value="">Optional exact zone</option>';
    }
});

citySelect?.addEventListener('change', async () => {
    const cityId = citySelect.value;
    if (!cityId) {
        if (zoneSelect) {
            zoneSelect.innerHTML = '<option value="">Optional exact zone</option>';
        }
        return;
    }
    await loadZones(cityId);
});

document.querySelectorAll('.js-open-location').forEach((button) => {
    button.addEventListener('click', () => {
        syncLocationInputs(config.initialLocation);
        showLocationModal();
    });
});

document.querySelectorAll('.js-filter-input').forEach((input) => {
    input.addEventListener('change', (event) => {
        event.preventDefault();
        loadFilteredProducts(input.form).catch(() => {
            input.form?.submit();
        });
    });
});

const searchInput = document.querySelector('.js-search-input');
const searchSuggestions = document.querySelector('.js-search-suggestions');
let searchSuggestionTimer = null;

function hideSearchSuggestions() {
    if (searchSuggestions) {
        searchSuggestions.hidden = true;
        searchSuggestions.innerHTML = '';
    }
}

function renderSearchSuggestions(items) {
    if (!searchSuggestions) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        hideSearchSuggestions();
        return;
    }

    searchSuggestions.innerHTML = items
        .map((item) => `<button type="button" class="sf-search-suggestion" data-value="${escapeHtml(item)}">${escapeHtml(item)}</button>`)
        .join('');
    searchSuggestions.hidden = false;
}

searchInput?.addEventListener('input', () => {
    const q = searchInput.value.trim();
    window.clearTimeout(searchSuggestionTimer);

    if (q.length < 2) {
        hideSearchSuggestions();
        return;
    }

    searchSuggestionTimer = window.setTimeout(async () => {
        try {
            const url = new URL(config.searchSuggestionsUrl, window.location.origin);
            url.searchParams.set('q', q);
            renderSearchSuggestions(await fetchJson(url));
        } catch (error) {
            hideSearchSuggestions();
            showError(error.message);
        }
    }, 180);
});

searchSuggestions?.addEventListener('click', (event) => {
    const button = event.target.closest('.sf-search-suggestion');
    if (!button || !searchInput) {
        return;
    }

    searchInput.value = button.dataset.value || button.textContent || '';
    hideSearchSuggestions();
    searchInput.form?.requestSubmit();
});

document.addEventListener('click', (event) => {
    if (!event.target.closest('.js-search-form')) {
        hideSearchSuggestions();
    }
});

if (config.initialLocation) {
    syncLocationInputs(config.initialLocation);
}

if (config.notificationsUrl) {
    loadNotifications();
    window.setInterval(loadNotifications, 10000);
}

if (config.currentUserRole === 'customer' && config.guestCartMerged) {
    clearGuestCartState();
} else if (config.currentUserRole === 'customer') {
    mergeGuestCartIfNeeded().catch(() => {});
} else if (Array.isArray(config.initialCartState) && config.initialCartState.length > 0) {
    setGuestCartState(config.initialCartState);
}
