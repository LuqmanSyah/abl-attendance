@php
    $id = $getId();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            map: null,
            marker: null,
            circle: null,
            latitude: $wire.$entangle('data.latitude'),
            longitude: $wire.$entangle('data.longitude'),
            radius: $wire.$entangle('data.radius_meters'),
            async initMap() {
                await window.ablLoadLeaflet();

                const fallbackLatitude = -6.2000000;
                const fallbackLongitude = 106.8166667;
                const lat = Number(this.latitude || fallbackLatitude);
                const lng = Number(this.longitude || fallbackLongitude);

                this.map = L.map(this.$refs.map).setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                }).addTo(this.map);

                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                this.circle = L.circle([lat, lng], { radius: Number(this.radius || 100) }).addTo(this.map);

                this.marker.on('dragend', () => this.setPoint(this.marker.getLatLng()));
                this.map.on('click', (event) => this.setPoint(event.latlng));

                this.$watch('radius', (value) => this.circle?.setRadius(Number(value || 100)));
                setTimeout(() => this.map.invalidateSize(), 250);
            },
            setPoint(point) {
                this.latitude = Number(point.lat).toFixed(7);
                this.longitude = Number(point.lng).toFixed(7);
                this.marker.setLatLng(point);
                this.circle.setLatLng(point);
            },
            useCurrentLocation() {
                if (! navigator.geolocation) {
                    return;
                }

                navigator.geolocation.getCurrentPosition((position) => {
                    const point = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };

                    this.setPoint(point);
                    this.map.setView([point.lat, point.lng], 16);
                });
            },
        }"
        x-init="initMap()"
        class="space-y-3"
    >
        <div
            x-ref="map"
            id="{{ $id }}-map"
            class="h-80 w-full overflow-hidden rounded-lg border border-gray-200"
        ></div>

        <button
            type="button"
            x-on:click="useCurrentLocation()"
            class="fi-btn fi-size-sm fi-btn-color-gray"
        >
            Gunakan lokasi saya
        </button>
    </div>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIINfQ4gn0P3MK6YV8c8FE2zZpVOxNV6kI0="
        crossorigin=""
    />
    <script>
        window.ablLoadLeaflet = window.ablLoadLeaflet || function () {
            if (window.L) {
                return Promise.resolve();
            }

            if (window.ablLeafletPromise) {
                return window.ablLeafletPromise;
            }

            window.ablLeafletPromise = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
                script.crossOrigin = '';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });

            return window.ablLeafletPromise;
        };
    </script>
</x-dynamic-component>
