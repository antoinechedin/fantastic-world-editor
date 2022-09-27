class FWE {
    static createMap(divId) {
        let map = L.map(divId, {
            minZoom: 0,
            maxZoom: 4
        });
        L.tileLayer(FWE_DATA.mapUrl, {
            attribution: "Vacarme"
        }).addTo(map);

        map.setView([0, 0], 2);
        return map;
    }

    static createTestMap(divId, url) {
        let map = L.map(divId, {
            minZoom: 0,
            maxZoom: 4
        });
        L.tileLayer(url, {
            attribution: "Vacarme"
        }).addTo(map);

        map.setView([0, 0], 2);
        return map;
    }

    static createDebugMap(divId) {
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
