@php
    $id = $getId();
    $googleMapsApiKey = config('services.google_maps.key');
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            map: null,
            marker: null,
            circle: null,
            autocomplete: null,
            locating: false,
            searchMessage: null,
            searchQuery: '',
            googleMapsApiKey: @js($googleMapsApiKey),
            locationTimeoutId: null,
            locationWatchId: null,
            resizeObserver: null,
            latitude: $wire.$entangle('data.latitude'),
            longitude: $wire.$entangle('data.longitude'),
            radius: $wire.$entangle('data.radius_meters'),
            async initMap() {
                if (this.map) {
                    return;
                }

                this.ensureGoogleMapsCriticalStyles();

                if (! this.googleMapsApiKey) {
                    this.searchMessage = 'Google Maps API key belum dikonfigurasi.';

                    return;
                }

                try {
                    await this.loadGoogleMaps();
                } catch (error) {
                    this.searchMessage = 'Google Maps gagal dimuat.';

                    return;
                }

                const fallbackLatitude = -6.2000000;
                const fallbackLongitude = 106.8166667;
                const lat = Number(this.latitude || fallbackLatitude);
                const lng = Number(this.longitude || fallbackLongitude);
                const position = { lat, lng };

                this.map = new google.maps.Map(this.$refs.map, {
                    center: position,
                    zoom: 16,
                    maxZoom: 20,
                    clickableIcons: false,
                    fullscreenControl: false,
                    mapTypeControl: false,
                    streetViewControl: false,
                    zoomControl: true,
                    zoomControlOptions: {
                        position: google.maps.ControlPosition.RIGHT_BOTTOM,
                    },
                });

                this.marker = new google.maps.Marker({
                    map: this.map,
                    position,
                    draggable: true,
                });

                this.circle = new google.maps.Circle({
                    map: this.map,
                    center: position,
                    radius: Number(this.radius || 100),
                    fillColor: '#f59e0b',
                    fillOpacity: 0.16,
                    strokeColor: '#f59e0b',
                    strokeOpacity: 0.88,
                    strokeWeight: 3,
                });

                this.marker.addListener('drag', () => {
                    const point = this.marker.getPosition();

                    if (point) {
                        this.circle.setCenter(point);
                    }
                });

                this.marker.addListener('dragend', () => {
                    const point = this.marker.getPosition();

                    if (point) {
                        this.setPointFromLatLng(point, false);
                    }
                });

                this.map.addListener('click', (event) => {
                    if (event.latLng) {
                        this.setPointFromLatLng(event.latLng);
                    }
                });

                this.initPlaceAutocomplete();

                this.$watch('radius', (value) => {
                    this.circle?.setRadius(Number(value || 100));
                });

                this.resizeObserver = new ResizeObserver(() => this.resizeMap());
                this.resizeObserver.observe(this.$refs.map);
                requestAnimationFrame(() => this.resizeMap());
                setTimeout(() => this.resizeMap(), 250);
                setTimeout(() => this.resizeMap(), 750);
            },
            ensureGoogleMapsCriticalStyles() {
                if (document.getElementById('abl-google-maps-critical-styles')) {
                    return;
                }

                const style = document.createElement('style');
                style.id = 'abl-google-maps-critical-styles';
                style.textContent = `
                    .abl-map-picker-shell { position: relative; }
                    .abl-map-picker-shell::after { border: 1px solid rgba(17, 24, 39, 0.12); border-radius: 0.5rem; box-shadow: inset 0 1px rgba(255, 255, 255, 0.3), 0 18px 44px rgba(15, 23, 42, 0.18); content: ''; inset: 0; pointer-events: none; position: absolute; z-index: 8; }
                    .abl-map-picker { background: #e5e7eb; display: block; height: 26rem; min-height: 26rem; position: relative; width: 100%; }
                    .abl-map-picker .gm-style { border-radius: inherit; }
                    .abl-map-picker .gm-style-mtc, .abl-map-picker .gm-svpc { display: none !important; }
                    .pac-container { border-radius: 0.75rem; box-shadow: 0 18px 36px rgba(15, 23, 42, 0.2); font-family: inherit; margin-top: 0.35rem; z-index: 9999; }
                    .pac-item { cursor: pointer; padding: 0.45rem 0.7rem; }
                    .pac-item-query { color: #111827; }
                    .abl-map-picker-overlay { align-items: center; backdrop-filter: blur(14px); background: rgba(255, 255, 255, 0.9) !important; border: 1px solid rgba(17, 24, 39, 0.1); border-radius: 0.75rem; box-shadow: 0 14px 32px rgba(15, 23, 42, 0.16); color: #111827 !important; display: inline-flex; font-size: 0.8125rem; gap: 0.45rem; line-height: 1; padding: 0.55rem 0.75rem; position: absolute; z-index: 10; }
                    .abl-map-picker-overlay-top { right: 0.75rem; top: 0.75rem; }
                    .abl-map-picker-overlay-bottom { bottom: 0.75rem; left: 0.75rem; }
                    .abl-map-picker-button { cursor: pointer; }
                    .abl-map-picker-button:disabled { cursor: wait; opacity: 0.72; }
                    .abl-map-search { align-items: stretch; display: flex; flex-direction: column; left: 0.75rem; max-width: calc(100% - 15rem); padding: 0.45rem; right: auto; top: 0.75rem; width: min(34rem, calc(100% - 1.5rem)); }
                    .abl-map-search-row { display: flex; gap: 0.45rem; width: 100%; }
                    .abl-map-search-input { background: rgba(255, 255, 255, 0.95) !important; border: 1px solid rgba(17, 24, 39, 0.12); border-radius: 0.55rem; color: #111827 !important; flex: 1; font-size: 0.875rem; min-width: 0; padding: 0.5rem 0.65rem; }
                    .abl-map-search-input:focus { border-color: #f59e0b; outline: none; }
                    .abl-map-search-message { color: rgba(17, 24, 39, 0.72) !important; font-size: 0.8125rem; margin-top: 0.45rem; padding: 0 0.2rem 0.1rem; }
                    @media (max-width: 64rem) {
                        .abl-map-search { max-width: calc(100% - 1.5rem); }
                        .abl-map-picker-overlay-top { top: 4.65rem; }
                    }
                    @media (max-width: 48rem) {
                        .abl-map-picker { height: 24rem; min-height: 24rem; }
                        .abl-map-picker-overlay { border-radius: 0.65rem; }
                        .abl-map-picker-overlay-top { left: 0.75rem; right: auto; top: 4.65rem; }
                        .abl-map-search { width: calc(100% - 1.5rem); }
                    }
                `;
                document.head.appendChild(style);
            },
            loadGoogleMaps() {
                if (window.google?.maps?.Map && window.google?.maps?.places?.Autocomplete) {
                    return Promise.resolve();
                }

                if (window.ablGoogleMapsPromise) {
                    return window.ablGoogleMapsPromise;
                }

                window.ablGoogleMapsPromise = new Promise((resolve, reject) => {
                    this.preconnectMapAssets();

                    const callbackName = 'ablGoogleMapsReady';
                    window[callbackName] = () => resolve();

                    const script = document.createElement('script');
                    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(this.googleMapsApiKey)}&v=weekly&language=id&region=ID&libraries=places&callback=${callbackName}`;
                    script.async = true;
                    script.defer = true;
                    script.onerror = () => reject(new Error('Google Maps script failed'));
                    document.head.appendChild(script);
                });

                return window.ablGoogleMapsPromise;
            },
            preconnectMapAssets() {
                [
                    'https://maps.googleapis.com',
                    'https://maps.gstatic.com',
                ].forEach((href) => {
                    if (document.querySelector(`link[rel=\'preconnect\'][href=\'${href}\']`)) {
                        return;
                    }

                    const link = document.createElement('link');
                    link.rel = 'preconnect';
                    link.href = href;
                    link.crossOrigin = '';
                    document.head.appendChild(link);
                });
            },
            resizeMap() {
                if (! this.map) {
                    return;
                }

                const center = this.marker?.getPosition() ?? this.map.getCenter();

                google.maps.event.trigger(this.map, 'resize');

                if (center) {
                    this.map.setCenter(center);
                }
            },
            readLatLng(point) {
                const lat = typeof point.lat === 'function' ? point.lat() : Number(point.lat);
                const lng = typeof point.lng === 'function' ? point.lng() : Number(point.lng);

                return { lat, lng };
            },
            setPoint(point) {
                this.setPointFromLatLng(point);
            },
            setPointFromLatLng(point, updateMarker = true) {
                const { lat, lng } = this.readLatLng(point);

                if (! Number.isFinite(lat) || ! Number.isFinite(lng)) {
                    return;
                }

                const position = { lat, lng };

                this.latitude = lat.toFixed(7);
                this.longitude = lng.toFixed(7);

                if (updateMarker) {
                    this.marker?.setPosition(position);
                }

                this.circle?.setCenter(position);
            },
            initPlaceAutocomplete() {
                if (! window.google?.maps?.places?.Autocomplete || ! this.$refs.searchInput) {
                    this.searchMessage = 'Google Places belum tersedia.';

                    return;
                }

                this.autocomplete = new google.maps.places.Autocomplete(this.$refs.searchInput, {
                    componentRestrictions: { country: 'id' },
                    fields: ['formatted_address', 'geometry', 'name'],
                });
                this.autocomplete.bindTo('bounds', this.map);

                this.autocomplete.addListener('place_changed', () => {
                    const place = this.autocomplete.getPlace();
                    const location = place.geometry?.location;

                    if (! location) {
                        this.searchMessage = 'Pilih lokasi dari saran Google Maps.';

                        return;
                    }

                    this.searchQuery = place.formatted_address || place.name || this.$refs.searchInput.value;
                    this.searchMessage = null;
                    this.setPointFromLatLng(location);

                    if (place.geometry?.viewport) {
                        google.maps.event.addListenerOnce(this.map, 'idle', () => {
                            if (this.map.getZoom() > 18) {
                                this.map.setZoom(18);
                            }
                        });

                        this.map.fitBounds(place.geometry.viewport, 56);

                        return;
                    }

                    this.map.panTo(location);
                    this.map.setZoom(17);
                });
            },
            useCurrentLocation() {
                if (this.locating) {
                    return;
                }

                if (! window.isSecureContext) {
                    alert('Lokasi browser di HP membutuhkan HTTPS. Buka aplikasi lewat HTTPS tunnel, bukan http://IP:8000.');

                    return;
                }

                if (! navigator.geolocation) {
                    alert('Browser tidak mendukung fitur lokasi.');

                    return;
                }

                this.locating = true;

                let bestPosition = null;
                let hasAppliedPosition = false;

                const clearLocationWatch = () => {
                    if (this.locationWatchId !== null) {
                        navigator.geolocation.clearWatch(this.locationWatchId);
                        this.locationWatchId = null;
                    }

                    if (this.locationTimeoutId !== null) {
                        clearTimeout(this.locationTimeoutId);
                        this.locationTimeoutId = null;
                    }
                };

                const applyPosition = (position) => {
                    const point = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };

                    this.setPoint(point);
                    this.map.panTo(point);
                    this.map.setZoom(position.coords.accuracy <= 50 ? 18 : 16);
                    hasAppliedPosition = true;
                };

                const finish = (shouldWarn = false) => {
                    clearLocationWatch();
                    this.locating = false;

                    if (bestPosition && ! hasAppliedPosition) {
                        applyPosition(bestPosition);
                    }

                    if (shouldWarn && bestPosition?.coords.accuracy > 100) {
                        alert(`Akurasi lokasi browser masih sekitar ${Math.round(bestPosition.coords.accuracy)} meter.`);
                    }
                };

                this.locationWatchId = navigator.geolocation.watchPosition(
                    (position) => {
                        const isBetterPosition = ! bestPosition || position.coords.accuracy < bestPosition.coords.accuracy;

                        if (! isBetterPosition) {
                            return;
                        }

                        bestPosition = position;
                        applyPosition(position);

                        if (position.coords.accuracy <= 50) {
                            finish();
                        }
                    },
                    (error) => {
                        finish();

                        if (error.code === error.PERMISSION_DENIED) {
                            alert('Izin lokasi ditolak. Aktifkan izin lokasi untuk browser ini.');

                            return;
                        }

                        if (error.code === error.POSITION_UNAVAILABLE) {
                            alert('Lokasi tidak tersedia. Pastikan GPS/lokasi HP aktif.');

                            return;
                        }

                        if (error.code === error.TIMEOUT) {
                            alert('Pengambilan lokasi terlalu lama. Coba lagi di area dengan sinyal lebih baik.');
                        }
                    },
                    {
                        enableHighAccuracy: true,
                        maximumAge: 0,
                        timeout: 15000,
                    },
                );

                this.locationTimeoutId = setTimeout(() => finish(true), 10000);
            },
            destroy() {
                if (this.locationWatchId !== null) {
                    navigator.geolocation.clearWatch(this.locationWatchId);
                }

                if (this.locationTimeoutId !== null) {
                    clearTimeout(this.locationTimeoutId);
                }

                this.resizeObserver?.disconnect();
                this.marker?.setMap(null);
                this.circle?.setMap(null);

                if (this.autocomplete) {
                    google.maps.event.clearInstanceListeners(this.autocomplete);
                }

                if (this.map) {
                    google.maps.event.clearInstanceListeners(this.map);
                }
            },
        }"
        x-init="initMap()"
        class="space-y-3"
    >
        <div class="abl-map-picker-shell">
            <div
                x-ref="map"
                wire:ignore
                id="{{ $id }}-map"
                class="abl-map-picker w-full overflow-hidden rounded-lg border border-gray-700"
                style="display: block; height: 26rem; min-height: 26rem; width: 100%; overflow: hidden; border: 1px solid rgb(55 65 81); border-radius: 0.5rem;"
            ></div>

            <div class="abl-map-picker-overlay abl-map-search">
                <div class="abl-map-search-row">
                    <input
                        x-ref="searchInput"
                        type="search"
                        x-model="searchQuery"
                        class="abl-map-search-input"
                        placeholder="Cari lokasi di Google Maps"
                    />
                </div>

                <div
                    x-show="searchMessage"
                    x-cloak
                    x-text="searchMessage"
                    class="abl-map-search-message"
                ></div>
            </div>

            <div class="abl-map-picker-overlay abl-map-picker-overlay-top">
                <span x-text="Number(latitude || 0).toFixed(5)"></span>
                <span>/</span>
                <span x-text="Number(longitude || 0).toFixed(5)"></span>
            </div>

            <button
                type="button"
                x-on:click="useCurrentLocation()"
                x-bind:disabled="locating"
                x-text="locating ? 'Mengambil lokasi...' : 'Gunakan lokasi saya'"
                class="abl-map-picker-overlay abl-map-picker-overlay-bottom abl-map-picker-button"
            >
                Gunakan lokasi saya
            </button>
        </div>
    </div>
</x-dynamic-component>
