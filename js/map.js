/* map.js — Google Maps con pin custom da KML
 * Da includere via wp_enqueue_script() in functions.php
 * Richiede: <div id="custom-map"></div> nel template
 */

window.initMap = async function initMap() {
  if (!document.getElementById("custom-map")) return;

  const { Map } = await google.maps.importLibrary("maps");
  const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");
  const { InfoWindow } = await google.maps.importLibrary("maps");

  const map = new Map(document.getElementById("custom-map"), {
    zoom: 2,
    center: { lat: 30, lng: 10 },
    mapId: "60b9e4d6aeca2ee7551f55b1",
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: true,
    zoomControl: true,
  });

  let openInfoWindow = null;

  MAP_LOCATIONS.forEach((loc) => {
    const marker = new AdvancedMarkerElement({
      position: { lat: loc.lat, lng: loc.lng },
      map,
      title: loc.name,

    });

    const infowindow = new InfoWindow({
      content: `
        <div class="map-infowindow">
          <strong>${loc.name}</strong>
          <p>${loc.description}</p>
        </div>
      `,
      maxWidth: 280,
    });

    marker.addListener("click", () => {
      if (openInfoWindow) openInfoWindow.close();
      infowindow.open(map, marker);
      openInfoWindow = infowindow;
    });
  });

  // Chiudi infowindow cliccando sulla mappa
  map.addListener("click", () => {
    if (openInfoWindow) {
      openInfoWindow.close();
      openInfoWindow = null;
    }
  });
}