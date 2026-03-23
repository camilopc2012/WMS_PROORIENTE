// odc.js - WMS v2 - Ordenes de Compra
window.ODC = {
    init() {
        console.log('ODC inicializado');
    },
    buscarProducto(q) {
        return fetch(`/api/odc/buscar-producto?q=${q}`).then(r=>r.json());
    }
};
