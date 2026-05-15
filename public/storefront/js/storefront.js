const config = window.storefrontConfig || {};
const cartDrawer = document.querySelector('.sf-cart-drawer');
const cartBackdrop = document.querySelector('.sf-drawer-backdrop');
const cartDrawerContent = document.querySelector('.js-cart-drawer');
const cartCountEls = document.querySelectorAll('.js-cart-count');
const locationLabelEls = document.querySelectorAll('.js-location-label');
const locationModalEl = document.getElementById('locationModal');
const locationModal = locationModalEl ? new bootstrap.Modal(locationModalEl) : null;
const checkoutAuthModalEl = document.getElementById('checkoutAuthModal');
const checkoutAuthModal = checkoutAuthModalEl ? new bootstrap.Modal(checkoutAuthModalEl) : null;
const addressDeleteConfirmModalEl = document.getElementById('addressDeleteConfirmModal');
const addressDeleteConfirmModal = addressDeleteConfirmModalEl ? new bootstrap.Modal(addressDeleteConfirmModalEl) : null;
const locationForm = locationModalEl?.querySelector('.js-location-form') || document.querySelector('.js-location-form');
const countrySelect = locationForm?.querySelector('.js-country-select') || null;
const citySelect = locationForm?.querySelector('.js-city-select') || null;
const zoneSelect = locationForm?.querySelector('.js-zone-select') || null;
const locationAlert = locationForm?.querySelector('.js-location-alert') || null;
const vendorSelector = document.querySelector('.js-vendor-selector');
const vendorList = document.querySelector('.js-vendor-list');
const selectedVendorText = document.querySelector('.js-selected-vendor-text');
const storefrontStatus = document.querySelector('.js-storefront-status');
const guestCartKey = 'expressbazar.guestCart';
const legacyGuestCartKey = 'guest_cart';
const selectedVendorIdKey = 'expressbazar.selectedVendorId';
const selectedVendorNameKey = 'expressbazar.selectedVendorName';
let pendingAddressDeleteForm = null;
window.storefrontAjaxFilters = true;

function hidePageLoader() {
    const loader = document.getElementById('pageLoader') || document.querySelector('.sf-page-loader');
    if (!loader) {
        return;
    }

    window.setTimeout(() => {
        loader.classList.add('is-hidden');
        window.setTimeout(() => {
            loader.style.display = 'none';
        }, 300);
    }, 120);
}

if (document.readyState === 'complete') {
    hidePageLoader();
} else {
    window.addEventListener('load', hidePageLoader, { once: true });
}

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

function showLocationAlert(message) {
    if (!locationAlert) {
        showError(message);
        return;
    }

    locationAlert.textContent = message || 'Delivery is not available in your area.';
    locationAlert.classList.remove('d-none');
}

function clearLocationAlert() {
    if (!locationAlert) {
        return;
    }

    locationAlert.textContent = '';
    locationAlert.classList.add('d-none');
}

function locationErrorMessage(payload) {
    return payload?.errors?.postcode?.[0]
        || payload?.errors?.zone_id?.[0]
        || payload?.errors?.city_id?.[0]
        || payload?.errors?.country_id?.[0]
        || payload?.errors?.location?.[0]
        || payload?.message
        || uiMessage('api_error', 'Something went wrong. Please try again');
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

function syncGuestCartState(state) {
    const normalized = normalizeCartState(state);

    if (normalized.length > 0) {
        setGuestCartState(normalized);
    } else {
        clearGuestCartState();
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
        const cartCount = Number(payload.cartCount || 0);
        cartCountEls.forEach((el) => {
            el.textContent = String(cartCount);
            el.classList.toggle('d-none', cartCount <= 0);
        });
        document.body.dataset.cartCount = String(cartCount);

        if (shouldMirrorGuestCart() && cartCount === 0) {
            clearGuestCartState();
        }
    }

    if (payload.drawerHtml && cartDrawerContent) {
        cartDrawerContent.innerHTML = payload.drawerHtml;
    }

    if (Array.isArray(payload.cartState) && shouldMirrorGuestCart()) {
        syncGuestCartState(payload.cartState);
    }

    if (payload.locationLabel) {
        locationLabelEls.forEach((el) => {
            el.textContent = payload.locationLabel;
        });
    }

    updateCartPage(payload);
    updateProductControls(payload);
}

function guestCartCount() {
    return normalizeCartState(getGuestCartState())
        .reduce((total, item) => total + item.quantity, 0);
}

function hydrateGuestCartCount() {
    if (!shouldMirrorGuestCart() || Number(document.body.dataset.cartCount || 0) > 0) {
        return;
    }

    if (Array.isArray(config.initialCartState) && config.initialCartState.length === 0) {
        clearGuestCartState();
        updateCartUi({ cartCount: 0 });
        return;
    }

    const count = guestCartCount();
    if (count > 0) {
        updateCartUi({ cartCount: count });
    }
}

function cartUrl(template, productId) {
    return String(template || '').replace('__ID__', String(productId));
}

function formatCartAmount(value) {
    const amount = Number(value || 0);
    const currency = config.storeCurrency || {};
    const symbol = currency.symbol || `${currency.code || 'INR'} `;

    return `${symbol}${Math.round(amount).toLocaleString(currency.locale || 'en-IN')}`;
}

function updateRailOverflow() {
    document.querySelectorAll('.sf-rail-wrap').forEach((wrap) => {
        const rail = wrap.querySelector('.sf-product-rail, .sf-chip-row');
        if (!rail) {
            wrap.classList.remove('has-overflow');
            wrap.classList.remove('can-scroll-left', 'can-scroll-right');
            return;
        }

        const maxScrollLeft = Math.max(rail.scrollWidth - rail.clientWidth, 0);
        const scrollLeft = Math.max(rail.scrollLeft, 0);
        const hasOverflow = maxScrollLeft > 4;

        wrap.classList.toggle('has-overflow', hasOverflow);
        wrap.classList.toggle('can-scroll-left', hasOverflow && scrollLeft > 4);
        wrap.classList.toggle('can-scroll-right', hasOverflow && scrollLeft < maxScrollLeft - 4);
    });
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

function updateProductControls(payload = {}) {
    const item = payload.cartItem;
    if (!item?.productId) {
        return;
    }

    const productId = String(item.productId);
    const quantity = Number(item.quantity || 0);

    document.querySelectorAll(`.js-add-to-cart[action$="/${productId}"]`).forEach((form) => {
        if (form.classList.contains('sf-card-add')) {
            return;
        }

        const stepper = document.createElement('div');
        stepper.className = 'sf-stepper';
        stepper.innerHTML = `
            <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="-1" data-product="${escapeHtml(productId)}">-</button>
            <span class="sf-stepper-value" data-cart-stepper-value>${quantity}</span>
            <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="1" data-product="${escapeHtml(productId)}">+</button>
        `;
        form.replaceWith(stepper);
    });

    document.querySelectorAll(`.js-cart-adjust[data-product="${productId}"]`)
        .forEach((button) => {
            const value = button.closest('.sf-stepper')?.querySelector('.sf-stepper-value');
            if (value) {
                value.textContent = String(quantity);
            }
        });
}

function showLocationModal() {
    clearLocationAlert();
    locationModal?.show();
}

async function fetchJson(url) {
    let response;
    let payload = {};

    try {
        response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });
        payload = await response.json();
    } catch (error) {
        throw new Error(uiMessage('api_error', 'Something went wrong. Please try again'));
    }

    if (!response.ok || payload?.error) {
        throw new Error(payload?.message || uiMessage('api_error', 'Something went wrong. Please try again'));
    }

    return payload;
}

function updateProductList(html) {
    const productList = document.querySelector('.js-product-list');
    if (productList && typeof html === 'string') {
        productList.innerHTML = html;
        updateRailOverflow();
    }
}

function updateStorefrontStatus(message = '') {
    if (!storefrontStatus) {
        return;
    }

    const text = String(message || '').trim();
    storefrontStatus.textContent = text;
    storefrontStatus.classList.toggle('d-none', text === '');
}

function notificationMessage(notification) {
    return notification?.message || notification?.data?.message || notification?.data?.title || 'Notification';
}

function notificationTitle(notification) {
    return notification?.title || notification?.data?.title || 'Notification';
}

function renderNotifications(data) {
    const container = document.getElementById('notification-list');
    const countEl = document.getElementById('notification-count');
    const clearAllButton = document.querySelector('.js-notifications-clear-all');

    if (!container) {
        return;
    }

    const notifications = Array.isArray(data) ? data : (data?.notifications || []);
    const unreadCount = Number(Array.isArray(data)
        ? notifications.filter((notification) => !notification.read_at).length
        : (data?.unread || 0));

    if (countEl) {
        countEl.textContent = String(unreadCount);
        countEl.classList.toggle('d-none', unreadCount < 1);
    }

    clearAllButton?.classList.toggle('d-none', unreadCount < 1);

    if (notifications.length === 0) {
        container.innerHTML = '<div class="dropdown-item-text small text-secondary px-2 py-2">No notifications</div>';
        return;
    }

    container.innerHTML = notifications
        .map((notification) => {
            const unreadClass = notification.read_at ? '' : 'fw-semibold';
            const title = escapeHtml(notificationTitle(notification));
            const message = escapeHtml(notificationMessage(notification));
            const id = escapeHtml(notification.id);

            return `
                <div class="dropdown-item-text small text-secondary px-2 py-2 js-notification-item ${unreadClass}">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <span>
                            <span class="d-block text-dark">${title}</span>
                            <span class="d-block">${message}</span>
                        </span>
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
        // Notifications are a background enhancement; avoid interrupting browsing.
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

async function clearAllNotifications() {
    if (!config.notificationReadAllUrl) {
        return;
    }

    try {
        const { response, payload } = await sendCartAction(config.notificationReadAllUrl, {
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

function selectedVendorIdFromUrl() {
    return new URL(window.location.href).searchParams.get('vendor_id') || '';
}

function savedVendorId() {
    return '';
}

function saveSelectedVendor(id, name) {
    try {
        localStorage.removeItem(selectedVendorIdKey);
        localStorage.removeItem(selectedVendorNameKey);
    } catch (error) {
        // Ignore storage errors.
    }
}

function clearSelectedVendor() {
    saveSelectedVendor('', '');
    if (selectedVendorText) {
        selectedVendorText.textContent = 'Vendors';
    }
}

function isVendorFilterablePage() {
    const path = window.location.pathname.replace(/\/+$/, '') || '/';

    return path === '/'
        || path === '/search'
        || path.startsWith('/categories/')
        || path.startsWith('/subcategories/');
}

function vendorFilterUrl(vendorId) {
    const target = new URL(isVendorFilterablePage() ? window.location.href : (config.homeUrl || '/'), window.location.origin);

    if (vendorId) {
        target.searchParams.set('vendor_id', vendorId);
    } else {
        target.searchParams.delete('vendor_id');
    }

    return target;
}

function reloadWithoutVendorFilter() {
    const url = new URL(window.location.href);
    url.searchParams.delete('vendor_id');
    url.hash = '';
    window.location.href = url.toString();
}

function applyVendorFilter(vendorId) {
    window.location.href = vendorFilterUrl(vendorId).toString();
}

async function loadVendors() {
    if (!vendorSelector || !vendorList || !selectedVendorText || !config.vendorsByLocationUrl) {
        return;
    }

    if (!config.initialLocation) {
        vendorSelector.classList.add('d-none');
        clearSelectedVendor();
        return;
    }

    vendorSelector.classList.remove('d-none');
    vendorList.innerHTML = '<li class="dropdown-item text-muted">Loading...</li>';

    try {
        if (Array.isArray(config.initialVendors) && config.initialVendors.length > 0) {
            renderVendorDropdown(config.initialVendors, { allowRedirect: false });
        }
        const vendors = await fetchJson(config.vendorsByLocationUrl);
        renderVendorDropdown(vendors);
    } catch (error) {
        if (Array.isArray(config.initialVendors) && config.initialVendors.length > 0) {
            renderVendorDropdown(config.initialVendors);
            return;
        }

        vendorList.innerHTML = '<li class="dropdown-item text-danger">Unable to load vendors</li>';
    }
}

function renderVendorDropdown(vendors, options = {}) {
    const allowRedirect = options.allowRedirect !== false;
    const requestedVendorId = String(config.initialSelectedVendorId || selectedVendorIdFromUrl() || '');
    const activeVendorId = requestedVendorId;
    const vendorItems = Array.isArray(vendors) ? vendors : [];
    const activeVendor = vendorItems.find((vendor) => String(vendor.id) === activeVendorId) || null;

    if (vendorItems.length === 0) {
        clearSelectedVendor();
        vendorList.innerHTML = '<li class="dropdown-item text-danger">No vendors available</li>';
        updateStorefrontStatus(uiMessage('no_vendors', 'No vendors available in your area'));
        return;
    }

    const items = vendorItems.map((vendor) => {
        const id = escapeHtml(vendor.id);
        const name = escapeHtml(vendor.name);
        const activeClass = String(vendor.id) === activeVendorId ? ' active' : '';

        return `<li><button type="button" class="dropdown-item js-vendor-item${activeClass}" data-id="${id}" data-name="${name}">${name}</button></li>`;
    });

    vendorList.innerHTML = [
        '<li><button type="button" class="dropdown-item js-vendor-item" data-id="" data-name="Vendors">Vendors</button></li>',
        '<li><hr class="dropdown-divider"></li>',
        ...items,
    ].join('');

    if (activeVendor) {
        selectedVendorText.textContent = activeVendor.name;
        saveSelectedVendor(activeVendor.id, activeVendor.name);
    } else {
        clearSelectedVendor();

        if (allowRedirect && requestedVendorId && isVendorFilterablePage()) {
            applyVendorFilter('');
        }
    }
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

async function loadCities(countryId, selectedCityId = null, targetCitySelect = citySelect) {
    if (!countryId || !targetCitySelect) {
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

    targetCitySelect.innerHTML = '<option value="">Choose city</option>';
    cities.forEach((city) => {
        const option = document.createElement('option');
        option.value = city.id;
        option.textContent = city.city_name;
        if (String(city.id) === String(selectedCityId)) {
            option.selected = true;
        }
        targetCitySelect.appendChild(option);
    });
}

async function loadZones(cityId, selectedZoneId = null, targetZoneSelect = zoneSelect) {
    if (!cityId || !targetZoneSelect) {
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

    targetZoneSelect.innerHTML = '<option value="">Optional exact zone</option>';
    zones.forEach((zone) => {
        const option = document.createElement('option');
        option.value = zone.id;
        option.textContent = `${zone.zone_name}${zone.zone_code ? ` (${zone.zone_code})` : ''}`;
        if (String(zone.id) === String(selectedZoneId)) {
            option.selected = true;
        }
        targetZoneSelect.appendChild(option);
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

function resetLocationForm(form = locationForm) {
    if (!form) {
        return;
    }

    const postcodeInput = form.querySelector('input[name="postcode"]');
    const forceClearInput = form.querySelector('input[name="force_clear"]');
    const formCountrySelect = form.querySelector('.js-country-select');
    const formCitySelect = form.querySelector('.js-city-select');
    const formZoneSelect = form.querySelector('.js-zone-select');

    if (postcodeInput) {
        postcodeInput.value = '';
    }
    if (forceClearInput) {
        forceClearInput.value = '0';
    }
    if (formCountrySelect) {
        formCountrySelect.value = '';
    }
    if (formCitySelect) {
        formCitySelect.innerHTML = '<option value="">Choose city</option>';
    }
    if (formZoneSelect) {
        formZoneSelect.innerHTML = '<option value="">Optional exact zone</option>';
    }

    clearLocationAlert();
}

document.addEventListener('click', async (event) => {
    const locationClearButton = event.target.closest('.js-location-clear');
    if (locationClearButton) {
        event.preventDefault();
        resetLocationForm(locationClearButton.closest('.js-location-form'));
        return;
    }

    const railButton = event.target.closest('.js-rail-scroll');
    if (railButton) {
        event.preventDefault();
        const rail = railButton.closest('.sf-rail-wrap')?.querySelector('.sf-product-rail, .sf-chip-row');
        const direction = Number(railButton.dataset.direction || 1);

        if (rail) {
            rail.scrollBy({
                left: direction * Math.max(rail.clientWidth * 0.8, 220),
                behavior: 'smooth',
            });
            window.setTimeout(updateRailOverflow, 280);
        }
        return;
    }

    const openCartTrigger = event.target.closest('.js-open-cart');
    if (openCartTrigger) {
        event.preventDefault();
        openCart();
        return;
    }

    const checkoutAuthTrigger = event.target.closest('.js-checkout-auth-required');
    if (checkoutAuthTrigger) {
        event.preventDefault();
        const serverCart = normalizeCartState(config.initialCartState || []);
        const cartToPreserve = serverCart.length > 0 ? serverCart : getGuestCartState();

        if (cartToPreserve.length > 0) {
            setGuestCartState(cartToPreserve);
        }

        checkoutAuthModal?.show();
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

    const notificationsClearAllButton = event.target.closest('.js-notifications-clear-all');
    if (notificationsClearAllButton) {
        event.preventDefault();
        clearAllNotifications();
        return;
    }

    const vendorItem = event.target.closest('.js-vendor-item');
    if (vendorItem) {
        event.preventDefault();
        const vendorId = vendorItem.dataset.id || '';
        const vendorName = vendorItem.dataset.name || vendorItem.textContent.trim() || 'Vendors';

        saveSelectedVendor(vendorId, vendorName);
        if (selectedVendorText) {
            selectedVendorText.textContent = vendorId ? vendorName : 'Vendors';
        }
        applyVendorFilter(vendorId);
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

    const addressDeleteForm = event.target.closest('.js-address-delete-form');
    if (addressDeleteForm && addressDeleteConfirmModal) {
        event.preventDefault();
        pendingAddressDeleteForm = addressDeleteForm;
        addressDeleteConfirmModal.show();
        return;
    }

    const form = event.target.closest('.js-location-form');
    if (!form) {
        return;
    }

    event.preventDefault();
    clearLocationAlert();
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
                    clearSelectedVendor();
                    reloadWithoutVendorFilter();
                }
            }
        } else if (payload?.errors || payload?.message) {
            showLocationAlert(locationErrorMessage(payload));
        } else {
            showLocationAlert(uiMessage('api_error', 'Something went wrong. Please try again'));
        }
        return;
    }

    updateCartUi(payload);
    locationModal?.hide();
    clearSelectedVendor();
    reloadWithoutVendorFilter();
});

document.querySelector('.js-confirm-address-delete')?.addEventListener('click', () => {
    if (!pendingAddressDeleteForm) {
        addressDeleteConfirmModal?.hide();
        return;
    }

    const form = pendingAddressDeleteForm;
    pendingAddressDeleteForm = null;
    addressDeleteConfirmModal?.hide();
    form.submit();
});

countrySelect?.addEventListener('change', async () => {
    clearLocationAlert();
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
    clearLocationAlert();
    const cityId = citySelect.value;
    if (!cityId) {
        if (zoneSelect) {
            zoneSelect.innerHTML = '<option value="">Optional exact zone</option>';
        }
        return;
    }
    await loadZones(cityId);
});

locationForm?.querySelector('input[name="postcode"]')?.addEventListener('input', clearLocationAlert);
zoneSelect?.addEventListener('change', clearLocationAlert);

document.querySelectorAll('.js-country-select').forEach((select) => {
    if (select === countrySelect) {
        return;
    }

    const form = select.closest('form');
    const relatedCitySelect = form?.querySelector('.js-city-select');
    const relatedZoneSelect = form?.querySelector('.js-zone-select');

    select.addEventListener('change', async () => {
        const countryId = select.value;
        if (!countryId) {
            if (relatedCitySelect) {
                relatedCitySelect.innerHTML = '<option value="">Choose city</option>';
            }
            if (relatedZoneSelect) {
                relatedZoneSelect.innerHTML = '<option value="">Optional exact zone</option>';
            }
            return;
        }

        if (relatedZoneSelect) {
            relatedZoneSelect.innerHTML = '<option value="">Optional exact zone</option>';
        }
        await loadCities(countryId, null, relatedCitySelect);
    });

    relatedCitySelect?.addEventListener('change', async () => {
        const cityId = relatedCitySelect.value;
        if (!cityId) {
            if (relatedZoneSelect) {
                relatedZoneSelect.innerHTML = '<option value="">Optional exact zone</option>';
            }
            return;
        }
        await loadZones(cityId, null, relatedZoneSelect);
    });
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
        .map((item) => {
            const value = typeof item === 'string' ? item : (item.name || '');
            return `<button type="button" class="sf-search-suggestion" data-value="${escapeHtml(value)}">${escapeHtml(value)}</button>`;
        })
        .join('');
    searchSuggestions.hidden = false;
}

searchInput?.addEventListener('input', () => {
    const q = searchInput.value.trim();
    window.clearTimeout(searchSuggestionTimer);

    if (q === '' && new URL(window.location.href).searchParams.has('search')) {
        const url = new URL(window.location.href);
        url.searchParams.delete('search');
        window.location.href = url.toString();
        return;
    }

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

loadVendors();

if (config.resetHomeVendorFilterOnLoad) {
    const url = new URL(window.location.href);
    url.searchParams.delete('vendor_id');
    window.history.replaceState({}, '', url);
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
    syncGuestCartState(config.initialCartState);
}

hydrateGuestCartCount();
updateRailOverflow();
document.querySelectorAll('.sf-product-rail, .sf-chip-row').forEach((rail) => {
    rail.addEventListener('scroll', updateRailOverflow, { passive: true });
});
window.addEventListener('resize', updateRailOverflow);
window.addEventListener('load', updateRailOverflow, { once: true });
