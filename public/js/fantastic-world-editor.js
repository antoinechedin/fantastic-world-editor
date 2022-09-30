class FantasticWorldEditor {

    constructor(obj) {
        this.mapUrl = obj.mapUrl;
        this.mapOption = {
            minZoom: 0,
            maxZoom: 4,
            zoomSnap: 0,
            zoomDelta: 0.5,
            wheelPxPerZoomLevel: 120,
        };
        this.icons = {};

        for (const [key, iconOptions] of Object.entries(obj.iconsOptions)) {
            this.icons[key] = L.icon(iconOptions);
        }
    }


    createMap(divId) {
        let map = L.map(divId, {
            minZoom: 0,
            maxZoom: 4,
            zoomSnap: 0,
            zoomDelta: 0.5,
            wheelPxPerZoomLevel: 120,
        });
        L.tileLayer(FWE_DATA.mapUrl, {
            attribution: "Vacarme"
        }).addTo(map);

        map.setView([0, 0], 2);
        return map;
    }

    createTestMap(divId, url) {
        let map = L.map(divId, {
            minZoom: 0,
            maxZoom: 4,
            zoomSnap: 0,
            zoomDelta: 0.5,
            wheelPxPerZoomLevel: 120,
        });
        L.tileLayer(url, {
            attribution: "Vacarme"
        }).addTo(map);

        map.setView([0, 0], 2);
        return map;
    }

    createDebugMap(divId) {
        let map = L.map(divId).setView([51.505, -0.09], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        L.marker([51.5, -0.09]).addTo(map)
            .bindPopup('A pretty CSS3 popup.<br> Easily customizable.')
            .openPopup();

        return map;
    }
}

const FWE = Object.freeze(new FantasticWorldEditor(FWE_DATA));
