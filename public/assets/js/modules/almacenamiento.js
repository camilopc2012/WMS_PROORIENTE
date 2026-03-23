/**
 * Prooriente WMS - Almacenamiento (Putaway & Traslado) Module
 */
window.Almacenamiento = {
    
    /* --- PUTAWAY (ACOMODO) --- */
    getPutawayHTML: function() {
        return `
            <div style="background:white; border-radius:12px; padding:25px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0; max-width:600px; margin:0 auto;">
                <div style="text-align:center; margin-bottom:24px;">
                    <div style="width:60px; height:60px; background:#f0fdf4; color:#22c55e; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px; font-size:1.5rem;">
                        <i class="fa-solid fa-pallet"></i>
                    </div>
                    <h3 style="margin:0; color:#0f172a;">Putaway (Acomodo)</h3>
                    <p style="color:#64748b; font-size:0.9rem; margin-top:5px;">Mueva mercancía de Recepción a Ubicaciones de Rack</p>
                </div>

                <div class="input-group">
                    <label>Escanear Placa / Producto en Patio</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" id="pa-scan" class="input-field" placeholder="EAN o Código de Barras">
                        <button class="btn-primary" style="width:50px;" onclick="window.Almacenamiento.buscarEnPatio()"><i class="fa-solid fa-search"></i></button>
                    </div>
                </div>

                <div id="pa-item-info" style="display:none; margin-top:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:20px;">
                    <div style="font-weight:700; color:#0f172a;" id="pa-prod-name">PROD NAME</div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
                        <div>
                            <span style="font-size:0.75rem; color:#64748b; display:block;">Lote</span>
                            <span id="pa-prod-lote" style="font-weight:600;">LOTE123</span>
                        </div>
                        <div>
                            <span style="font-size:0.75rem; color:#64748b; display:block;">Cantidad en Patio</span>
                            <span id="pa-prod-cant" style="font-weight:600; color:#ef4444;">10 UN</span>
                        </div>
                    </div>

                    <div style="margin-top:20px; border-top:1px solid #e2e8f0; padding-top:15px;">
                        <div class="input-group">
                            <label style="color:#22c55e; font-weight:700;">Ubicación de Destino (Escanee Rack)</label>
                            <input type="text" id="pa-dest" class="input-field" placeholder="Ej: R1-A2-B3">
                        </div>
                        <div class="input-group">
                            <label>Cantidad a Mover</label>
                            <input type="number" id="pa-move-cant" class="input-field" value="0">
                        </div>
                        <button class="btn-primary" style="background:#22c55e; margin-top:10px;" onclick="window.Almacenamiento.ejecutarPutaway()">Confirmar Acomodo</button>
                    </div>
                </div>
            </div>
        `;
    },

    buscarEnPatio: async function() {
        const query = document.getElementById('pa-scan').value;
        if(!query) return;
        try {
            // Logic to find stock in PATIO locations. For now generic inventory fetch.
            const res = await window.api.get('/param/productos');
            const p = res.data.find(x => x.codigo_interno === query || x.ean13 === query);
            if(p) {
                this._activePaProduct = p;
                document.getElementById('pa-item-info').style.display = 'block';
                document.getElementById('pa-prod-name').innerText = p.nombre;
                document.getElementById('pa-prod-cant').innerText = 'Disp local info...';
                document.getElementById('pa-move-cant').value = 1;
            }
        } catch(e) {}
    },

    /* --- TRASLADO INTERNO --- */
    getTrasladoHTML: function() {
        return `
             <div style="background:white; border-radius:12px; padding:25px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0; max-width:600px; margin:0 auto;">
                <div style="text-align:center; margin-bottom:24px;">
                    <div style="width:60px; height:60px; background:#eff6ff; color:#3b82f6; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px; font-size:1.5rem;">
                        <i class="fa-solid fa-people-carry-box"></i>
                    </div>
                    <h3 style="margin:0; color:#0f172a;">Traslado de Inventario</h3>
                </div>

                <div style="display:grid; gap:15px;">
                    <div class="input-group">
                        <label>Ubicación Origen</label>
                        <input type="text" id="tr-orig" class="input-field" placeholder="Escanee origen">
                    </div>
                    <div class="input-group">
                        <label>Producto / EAN</label>
                        <input type="text" id="tr-prod" class="input-field" placeholder="Escanee producto">
                    </div>
                     <div class="input-group">
                        <label>Cantidad</label>
                        <input type="number" id="tr-cant" class="input-field" value="1">
                    </div>
                    <div class="input-group">
                        <label style="color:#3b82f6; font-weight:700;">Ubicación Destino</label>
                        <input type="text" id="tr-dest" class="input-field" placeholder="Escanee destino">
                    </div>
                    <button class="btn-primary" style="background:#3b82f6; margin-top:20px;" onclick="window.Almacenamiento.ejecutarTraslado()">Confirmar Traslado</button>
                </div>
            </div>
        `;
    },

    ejecutarTraslado: async function() {
        const payload = {
            producto_id: document.getElementById('tr-prod').value, // Normally resolve EAN -> ID
            ubicacion_origen_id: 1, // Resolve string -> ID
            ubicacion_destino_id: 2,
            cantidad: document.getElementById('tr-cant').value
        };
        try {
            await window.api.post('/inventario/traslado', payload);
            window.showToast('Traslado exitoso', 'success');
            window.goToHome();
        } catch(e) { window.showToast(e.message, 'error'); }
    }
};
