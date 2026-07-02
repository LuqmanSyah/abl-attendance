@php
    $id = $getId();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            map: null,
            markerFeature: null,
            circleFeature: null,
            markerSource: null,
            circleSource: null,
            modifyInteraction: null,
            locating: false,
            searchMessage: null,
            searchQuery: '',
            searchResults: [],
            searchingLocation: false,
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

                await this.loadOpenLayers();

                const fallbackLatitude = -6.2000000;
                const fallbackLongitude = 106.8166667;
                const lat = Number(this.latitude || fallbackLatitude);
                const lng = Number(this.longitude || fallbackLongitude);
                const center = ol.proj.fromLonLat([lng, lat]);

                this.markerFeature = new ol.Feature({
                    geometry: new ol.geom.Point(center),
                });
                this.markerFeature.setStyle(
                    new ol.style.Style({
                        image: new ol.style.Circle({
                            radius: 9,
                            fill: new ol.style.Fill({ color: '#f59e0b' }),
                            stroke: new ol.style.Stroke({ color: '#111827', width: 3 }),
                        }),
                    }),
                );

                this.circleFeature = new ol.Feature({
                    geometry: new ol.geom.Circle(center, Number(this.radius || 100)),
                });
                this.circleFeature.setStyle(
                    new ol.style.Style({
                        fill: new ol.style.Fill({ color: 'rgba(245, 158, 11, 0.16)' }),
                        stroke: new ol.style.Stroke({ color: 'rgba(245, 158, 11, 0.88)', width: 3 }),
                    }),
                );

                this.markerSource = new ol.source.Vector({ features: [this.markerFeature] });
                this.circleSource = new ol.source.Vector({ features: [this.circleFeature] });

                this.map = new ol.Map({
                    target: this.$refs.map,
                    layers: [
                        new ol.layer.Tile({
                            preload: 2,
                            source: new ol.source.OSM({
                                attributions: '&copy; OpenStreetMap contributors',
                                maxZoom: 19,
                            }),
                        }),
                        new ol.layer.Vector({
                            source: this.circleSource,
                        }),
                        new ol.layer.Vector({
                            source: this.markerSource,
                        }),
                    ],
                    view: new ol.View({
                        center,
                        zoom: 16,
                    }),
                });

                this.modifyInteraction = new ol.interaction.Modify({
                    source: this.markerSource,
                    style: null,
                });
                this.map.addInteraction(this.modifyInteraction);

                this.modifyInteraction.on('modifyend', () => {
                    const point = ol.proj.toLonLat(this.markerFeature.getGeometry().getCoordinates());

                    this.setPointFromLonLat(point, false);
                });

                this.map.on('singleclick', (event) => {
                    this.setPointFromLonLat(ol.proj.toLonLat(event.coordinate));
                });

                this.$watch('radius', (value) => {
                    this.circleFeature
                        ?.getGeometry()
                        ?.setRadius(Number(value || 100));
                });

                this.resizeObserver = new ResizeObserver(() => this.map?.updateSize());
                this.resizeObserver.observe(this.$refs.map);
                requestAnimationFrame(() => this.map.updateSize());
                setTimeout(() => this.map.updateSize(), 250);
                setTimeout(() => this.map.updateSize(), 750);
            },
            async loadOpenLayers() {
                this.ensureOpenLayersCriticalStyles();

                await Promise.all([
                    this.loadOpenLayersStylesheet(),
                    this.loadOpenLayersScript(),
                ]);
            },
            ensureOpenLayersCriticalStyles() {
                if (document.getElementById('abl-openlayers-critical-styles')) {
                    return;
                }

                const style = document.createElement('style');
                style.id = 'abl-openlayers-critical-styles';
                style.textContent = `
                    .abl-map-picker-shell { position: relative; }
                    .abl-map-picker { background: #dbeafe; display: block; height: 26rem; min-height: 26rem; position: relative; width: 100%; }
                    .abl-map-picker .ol-viewport { border-radius: inherit; }
                    .abl-map-picker .ol-control { background: rgba(17, 24, 39, 0.72); border-radius: 0.5rem; padding: 0.2rem; }
                    .abl-map-picker .ol-control button { background: rgba(255, 255, 255, 0.94); border-radius: 0.35rem; color: #111827; cursor: pointer; font-weight: 700; height: 1.8rem; line-height: 1.8rem; margin: 0.1rem; width: 1.8rem; }
                    .abl-map-picker .ol-control button:hover { background: #f59e0b; color: #111827; }
                    .abl-map-picker .ol-zoom { left: 0.75rem; top: 4.55rem; }
                    .abl-map-picker .ol-attribution { background: rgba(17, 24, 39, 0.64); border-radius: 0.35rem 0 0 0; bottom: 0; color: #ffffff; font-size: 0.72rem; right: 0; }
                    .abl-map-picker .ol-attribution a { color: #ffffff; }
                    .abl-map-picker-overlay { align-items: center; backdrop-filter: blur(8px); background: rgba(17, 24, 39, 0.84); border: 1px solid rgba(255, 255, 255, 0.14); border-radius: 0.55rem; box-shadow: 0 10px 24px rgba(0, 0, 0, 0.28); color: #ffffff; display: inline-flex; font-size: 0.8125rem; gap: 0.45rem; line-height: 1; padding: 0.55rem 0.75rem; position: absolute; z-index: 10; }
                    .abl-map-picker-overlay-top { right: 0.75rem; top: 4.65rem; }
                    .abl-map-picker-overlay-bottom { bottom: 0.75rem; left: 0.75rem; }
                    .abl-map-picker-button { cursor: pointer; }
                    .abl-map-picker-button:disabled { cursor: wait; opacity: 0.72; }
                    .abl-map-search { align-items: stretch; display: flex; flex-direction: column; left: 0.75rem; max-width: min(34rem, calc(100% - 1.5rem)); padding: 0.45rem; right: 0.75rem; top: 0.75rem; }
                    .abl-map-search-row { display: flex; gap: 0.45rem; width: 100%; }
                    .abl-map-search-input { background: rgba(255, 255, 255, 0.95); border: 1px solid rgba(17, 24, 39, 0.18); border-radius: 0.4rem; color: #111827; flex: 1; font-size: 0.875rem; min-width: 0; padding: 0.5rem 0.65rem; }
                    .abl-map-search-input:focus { border-color: #f59e0b; outline: none; }
                    .abl-map-search-submit { background: #f59e0b; border-radius: 0.4rem; color: #111827; cursor: pointer; font-size: 0.8125rem; font-weight: 700; padding: 0.5rem 0.7rem; white-space: nowrap; }
                    .abl-map-search-submit:disabled { cursor: wait; opacity: 0.72; }
                    .abl-map-search-results { background: rgba(17, 24, 39, 0.94); border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 0.5rem; margin-top: 0.45rem; max-height: 13rem; overflow-y: auto; padding: 0.25rem; width: 100%; }
                    .abl-map-search-result { border-radius: 0.35rem; color: #ffffff; cursor: pointer; display: block; font-size: 0.8125rem; line-height: 1.35; padding: 0.5rem 0.6rem; text-align: left; width: 100%; }
                    .abl-map-search-result:hover { background: rgba(245, 158, 11, 0.2); }
                    .abl-map-search-message { color: rgba(255, 255, 255, 0.82); font-size: 0.8125rem; margin-top: 0.45rem; padding: 0 0.2rem 0.1rem; }
                `;
                document.head.appendChild(style);
            },
            loadOpenLayersStylesheet() {
                if (window.ablOpenLayersCssPromise) {
                    return window.ablOpenLayersCssPromise;
                }

                window.ablOpenLayersCssPromise = new Promise((resolve) => {
                    this.preconnectMapAssets();

                    const existing = document.querySelector('link[data-abl-openlayers-css]');

                    if (existing) {
                        resolve();

                        return;
                    }

                    const link = document.createElement('link');
                    link.dataset.ablOpenlayersCss = 'true';
                    link.rel = 'stylesheet';
                    link.href = 'https://cdn.jsdelivr.net/npm/ol@10.9.0/ol.css';
                    link.onload = resolve;
                    link.onerror = resolve;
                    document.head.appendChild(link);
                });

                return window.ablOpenLayersCssPromise;
            },
            preconnectMapAssets() {
                [
                    'https://cdn.jsdelivr.net',
                    'https://nominatim.openstreetmap.org',
                    'https://tile.openstreetmap.org',
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
            loadOpenLayersScript() {
                if (window.ol) {
                    return Promise.resolve();
                }

                if (window.ablOpenLayersPromise) {
                    return window.ablOpenLayersPromise;
                }

                window.ablOpenLayersPromise = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/ol@10.9.0/dist/ol.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });

                return window.ablOpenLayersPromise;
            },
            setPoint(point) {
                this.setPointFromLonLat([Number(point.lng), Number(point.lat)]);
            },
            setPointFromLonLat(point, updateMarker = true) {
                const lng = Number(point[0]);
                const lat = Number(point[1]);
                const coordinate = ol.proj.fromLonLat([lng, lat]);

                this.latitude = lat.toFixed(7);
                this.longitude = lng.toFixed(7);

                if (updateMarker) {
                    this.markerFeature.getGeometry().setCoordinates(coordinate);
                }

                this.circleFeature.getGeometry().setCenter(coordinate);
            },
            async searchLocation() {
                const query = this.searchQuery.trim();

                if (query.length < 3 || this.searchingLocation) {
                    return;
                }

                this.searchingLocation = true;
                this.searchMessage = null;
                this.searchResults = [];

                const params = new URLSearchParams({
                    addressdetails: '1',
                    format: 'jsonv2',
                    limit: '6',
                    q: query,
                    'accept-language': 'id',
                });

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/search?${params.toString()}`, {
                        headers: {
                            Accept: 'application/json',
                        },
                    });

                    if (! response.ok) {
                        throw new Error('Search failed');
                    }

                    this.searchResults = await response.json();
                    this.searchMessage = this.searchResults.length ? null : 'Lokasi tidak ditemukan.';
                } catch (error) {
                    this.searchMessage = 'Pencarian lokasi gagal.';
                } finally {
                    this.searchingLocation = false;
                }
            },
            selectSearchResult(result) {
                const lat = Number(result.lat);
                const lng = Number(result.lon);

                if (! Number.isFinite(lat) || ! Number.isFinite(lng)) {
                    return;
                }

                this.searchQuery = result.display_name;
                this.searchMessage = null;
                this.searchResults = [];
                this.setPointFromLonLat([lng, lat]);

                if (Array.isArray(result.boundingbox) && result.boundingbox.length === 4) {
                    const extent = ol.proj.transformExtent(
                        [
                            Number(result.boundingbox[2]),
                            Number(result.boundingbox[0]),
                            Number(result.boundingbox[3]),
                            Number(result.boundingbox[1]),
                        ],
                        'EPSG:4326',
                        'EPSG:3857',
                    );

                    this.map.getView().fit(extent, {
                        duration: 250,
                        maxZoom: 18,
                        padding: [80, 56, 56, 56],
                    });

                    return;
                }

                this.map.getView().animate({
                    center: ol.proj.fromLonLat([lng, lat]),
                    duration: 250,
                    zoom: 17,
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
                    this.map.getView().animate({
                        center: ol.proj.fromLonLat([point.lng, point.lat]),
                        duration: 250,
                        zoom: position.coords.accuracy <= 50 ? 18 : 16,
                    });
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
                this.map?.setTarget(undefined);
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
                        type="search"
                        x-model="searchQuery"
                        x-on:keydown.enter.prevent="searchLocation()"
                        class="abl-map-search-input"
                        placeholder="Cari lokasi"
                    />

                    <button
                        type="button"
                        x-on:click="searchLocation()"
                        x-bind:disabled="searchingLocation"
                        x-text="searchingLocation ? 'Mencari...' : 'Cari'"
                        class="abl-map-search-submit"
                    >
                        Cari
                    </button>
                </div>

                <div
                    x-show="searchResults.length > 0"
                    x-cloak
                    class="abl-map-search-results"
                >
                    <template x-for="result in searchResults" x-bind:key="result.place_id">
                        <button
                            type="button"
                            x-on:click="selectSearchResult(result)"
                            class="abl-map-search-result"
                            x-text="result.display_name"
                        ></button>
                    </template>
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
