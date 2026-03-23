/**
 * Prooriente WMS - Orden de Compra (ODC) Module
 */
window.ODC = {
    getODCHTML: function() {
        return `
            <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0; margin-bottom: 20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h4 style="margin:0; color:#0f172a;">Órdenes de Compra (ODC)</h4>
                    <button class="btn-primary" style="padding:8px 16px; width:auto; border-radius:8px; font-size:0.9rem;" onclick="window.ODC.showODCForm()"><i class="fa-solid fa-file-circle-plus"></i> Nueva ODC</button>
                </div>
                ${filterBarHTML('odc-tbody', '🔍 Buscar por número, proveedor...')}
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem;">
                        <thead>
                            <tr style="border-bottom:2px solid #e2e8f0; color:#64748b;">
                                <th style="padding:10px 8px;">Número</th>
                                <th style="padding:10px 8px;">Proveedor</th>
                                <th style="padding:10px 8px;">Fecha</th>
                                <th style="padding:10px 8px;">Estado</th>
                                <th style="padding:10px 8px; width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody id="odc-tbody">
                            <tr><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando órdenes...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Template -->
            <div id="form-odc-container" style="display:none; background:white; border-radius:12px; padding:25px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); border:1px solid #e2e8f0; max-width:700px; margin:0 auto 30px;">
                <h4 style="margin-top:0; color:#0f172a; margin-bottom: 20px;">Registrar Orden de Compra</h4>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="input-group">
                        <label>Proveedor *</label>
                        <select id="odc-prov" class="input-field"></select>
                    </div>
                    <div class="input-group">
                        <label>Número ODC *</label>
                        <input type="text" id="odc-num" class="input-field" placeholder="ODC-2024-XXX">
                    </div>
                    <div class="input-group">
                        <label>Fecha *</label>
                        <input type="date" id="odc-fecha" class="input-field">
                    </div>
                    <div class="input-group" style="grid-column: span 2;">
                        <label>Observaciones</label>
                        <textarea id="odc-obs" class="input-field" style="height:60px;"></textarea>
                    </div>
                </div>
                
                <h5 style="margin:20px 0 10px; color:#475569;">Productos en la Orden</h5>
                <div id="odc-items-list" style="margin-bottom:15px; border:1px solid #f1f5f9; border-radius:8px; padding:10px; background:#f8fafc;">
                    <div style="display:flex; gap:10px; margin-bottom:10px;">
                        <select id="odc-item-prod" class="input-field" style="flex:2;"></select>
                        <input type="number" id="odc-item-cant" class="input-field" style="flex:1;" placeholder="Cant.">
                        <button class="btn-primary" style="width:40px;" onclick="window.ODC.addItem()"><i class="fa-solid fa-plus"></i></button>
                    </div>
                    <table style="width:100%; font-size:0.85rem; border-collapse:collapse;">
                        <tbody id="odc-items-tbody"></tbody>
                    </table>
                </div>

                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button class="btn-primary" style="flex:1;" onclick="window.ODC.saveODC()">Guardar Orden</button>
                    <button class="btn-primary" style="flex:1; background:#cbd5e1; color:#334155;" onclick="document.getElementById('form-odc-container').style.display='none'">Cancelar</button>
                </div>
            </div>
        `;
    },

    currentItems: [],

    loadODCs: async function() {
        const tbody = document.getElementById('odc-tbody');
        if(!tbody) return;
        try {
            const res = await window.api.get('/odc');
            let html = '';
            res.data.forEach(o => {
                html += `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px 8px; font-weight:600;">${o.numero_odc}</td>
                        <td style="padding:12px 8px;">${o.proveedor ? o.proveedor.nombre : 'Unknown'}</td>
                        <td style="padding:12px 8px;">${o.fecha}</td>
                        <td style="padding:12px 8px;"><span class="badge ${o.estado}">${o.estado}</span></td>
                        <td style="padding:12px 8px; text-align:right;">
                            <button onclick="window.ODC.viewODC(${o.id})" class="btn-icon"><i class="fa-solid fa-eye"></i></button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html || '<tr><td colspan="5" style="text-align:center; padding:20px;">No hay órdenes de compra registradas.</td></tr>';
        } catch(e) { window.showToast('Error cargando ODC', 'error'); }
    },

    showODCForm: async function() {
        document.getElementById('form-odc-container').style.display = 'block';
        this.currentItems = [];
        this.renderItems();
        // Load props
        try {
            const resProv = await window.api.get('/param/proveedores');
            const selProv = document.getElementById('odc-prov');
            selProv.innerHTML = resProv.data.map(p => `<option value="${p.id}">${p.nombre}</option>`).join('');

            const resProd = await window.api.get('/param/productos');
            const selProd = document.getElementById('odc-item-prod');
            selProd.innerHTML = resProd.data.map(p => `<option value="${p.id}">${p.nombre}</option>`).join('');
        } catch(e) {}
    },

    addItem: function() {
        const prodId = document.getElementById('odc-item-prod').value;
        const prodName = document.getElementById('odc-item-prod').options[document.getElementById('odc-item-prod').selectedIndex].text;
        const cant = document.getElementById('odc-item-cant').value;
        if(!cant || cant <= 0) return;

        this.currentItems.push({ producto_id: prodId, nombre: prodName, cantidad_solicitada: cant });
        this.renderItems();
        document.getElementById('odc-item-cant').value = '';
    },

    renderItems: function() {
        const tbody = document.getElementById('odc-items-tbody');
        tbody.innerHTML = this.currentItems.map((item, idx) => `
            <tr style="border-bottom:1px solid #e2e8f0;">
                <td style="padding:8px;">${item.nombre}</td>
                <td style="padding:8px; width:80px; text-align:center;">${item.cantidad_solicitada}</td>
                <td style="padding:8px; width:40px; text-align:right;">
                    <i class="fa-solid fa-trash" style="color:#ef4444; cursor:pointer;" onclick="window.ODC.removeItem(${idx})"></i>
                </td>
            </tr>
        `).join('');
    },

    removeItem: function(idx) {
        this.currentItems.splice(idx, 1);
        this.renderItems();
    },

    saveODC: async function() {
        const payload = {
            proveedor_id: document.getElementById('odc-prov').value,
            numero_odc: document.getElementById('odc-num').value,
            fecha: document.getElementById('odc-fecha').value,
            observaciones: document.getElementById('odc-obs').value,
            detalles: this.currentItems
        };
        if(!payload.numero_odc || !payload.fecha || payload.detalles.length === 0) {
            return window.showToast('Número, Fecha e Items son requeridos', 'error');
        }
        try {
            await window.api.post('/odc', payload);
            window.showToast('Orden de Compra guardada');
            document.getElementById('form-odc-container').style.display = 'none';
            this.loadODCs();
        } catch(e) { window.showToast(e.message, 'error'); }
    }
};
