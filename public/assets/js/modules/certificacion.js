/**
 * Prooriente WMS - Certificación de Despacho Module
 */
window.Certificacion = {
    currentCert: null,
    tipo: 'Consolidado', // or 'Detalle'

    getCertificacionHTML: function(tipo) {
        this.tipo = tipo;
        const title = tipo === 'Consolidado' ? 'Certificación Consolidada' : 'Certificación por Cliente';
        return `
            <div style="max-width:800px; margin:0 auto;">
                <div id="cert-setup" style="background:white; border-radius:12px; padding:25px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0; text-align:center;">
                    <i class="fa-solid ${tipo === 'Consolidado' ? 'fa-cubes-stacked' : 'fa-clipboard-user'}" style="font-size:3rem; color:#f59e0b; margin-bottom:20px;"></i>
                    <h3 style="margin:0; color:#0f172a;">${title}</h3>
                    <p style="color:#64748b; margin-bottom:30px;">Inicie la certificación de la separación/despacho</p>
                    
                    <div style="margin-bottom:20px; text-align:left;">
                        <label style="display:block; font-size:0.85rem; color:#64748b; margin-bottom:5px;">Observaciones Iniciales</label>
                        <textarea id="cert-obs-init" class="input-field" style="height:60px;" placeholder="Ej: Ruta Norte - Vehículo ABC-123"></textarea>
                    </div>

                    <button class="btn-primary" style="background:#0f172a;" onclick="window.Certificacion.start()">Iniciar Certificación</button>
                </div>

                <div id="cert-active" style="display:none; background:white; border-radius:12px; padding:20px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); border:1px solid #e2e8f0;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #f1f5f9; padding-bottom:15px;">
                        <div>
                            <span style="font-size:0.75rem; color:#64748b; text-transform:uppercase;">Certificando (${this.tipo})</span>
                            <h4 id="cert-id-display" style="margin:0; color:#0f172a;">ID: ...</h4>
                        </div>
                        <button class="btn-primary" style="background:#ef4444; width:auto; font-size:0.8rem;" onclick="window.Certificacion.finish()">Finalizar y Validar</button>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                        ${this.tipo === 'Detalle' ? `
                        <div class="input-group" style="grid-column: span 2;">
                           <label>Seleccionar Cliente</label>
                           <select id="cert-cliente" class="input-field"></select>
                        </div>` : ''}
                        
                        <div class="input-group" style="grid-column: span 2;">
                           <label>Escanear Producto</label>
                           <div style="display:flex; gap:10px;">
                               <input type="text" id="cert-scan" class="input-field" placeholder="EAN o Código...">
                               <button class="btn-primary" style="width:50px;" onclick="window.Certificacion.buscar()"><i class="fa-solid fa-search"></i></button>
                           </div>
                        </div>
                        <div class="input-group">
                            <label>Cantidad Contada</label>
                            <input type="number" id="cert-cant" class="input-field" value="1">
                        </div>
                        <div class="input-group">
                            <label>Unidad</label>
                            <input type="text" id="cert-um" class="input-field" readonly value="UN">
                        </div>
                        <button class="btn-primary" style="grid-column: span 2; background:#0f172a;" onclick="window.Certificacion.add()">Registrar Hallazgo</button>
                    </div>

                    <div style="border-top:1px solid #f1f5f9; padding-top:15px;">
                        <h5 style="margin:0 0 10px 0; color:#475569;">Avance de Certificación</h5>
                        <div id="cert-list" style="max-height:300px; overflow-y:auto; border:1px solid #f1f5f9; border-radius:8px;">
                            <div style="padding:20px; text-align:center; color:#94a3b8; font-size:0.85rem;">Esperando registros...</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    start: async function() {
        const obs = document.getElementById('cert-obs-init').value;
        try {
            const res = await window.api.post('/certificaciones/start', { tipo: this.tipo, observaciones: obs });
            this.currentCert = res.id;
            document.getElementById('cert-setup').style.display = 'none';
            document.getElementById('cert-active').style.display = 'block';
            document.getElementById('cert-id-display').innerText = 'ID: ' + res.id;

            if(this.tipo === 'Detalle') {
                const resCli = await window.api.get('/param/clientes');
                document.getElementById('cert-cliente').innerHTML = resCli.data.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
            }
        } catch(e) { window.showToast(e.message, 'error'); }
    },

    buscar: async function() {
        const query = document.getElementById('cert-scan').value;
        try {
            const res = await window.api.get('/param/productos');
            const p = res.data.find(x => x.codigo_interno === query || x.ean13 === query || x.nombre.toLowerCase().includes(query.toLowerCase()));
            if(p) {
                this._lastProd = p;
                window.showToast('Producto: ' + p.nombre, 'success');
                document.getElementById('cert-um').value = p.unidad_medida || 'UN';
                document.getElementById('cert-cant').focus();
            } else { window.showToast('No encontrado', 'error'); }
        } catch(e) {}
    },

    items: [],

    add: async function() {
        if(!this._lastProd) return window.showToast('Busque un producto', 'error');
        const cant = parseFloat(document.getElementById('cert-cant').value);
        const cliId = this.tipo === 'Detalle' ? document.getElementById('cert-cliente').value : null;

        const payload = {
            producto_id: this._lastProd.id,
            cliente_id: cliId,
            cantidad_esperada: 0, // In this simple version we don't know expected yet, or assume 0 for blind count
            cantidad_contada: cant
        };

        try {
            await window.api.post(`/certificaciones/${this.currentCert}/linea`, payload);
            this.items.push({ nombre: this._lastProd.nombre, cant: cant });
            this.render();
            document.getElementById('cert-scan').value = '';
            document.getElementById('cert-cant').value = '1';
            this._lastProd = null;
        } catch(e) { window.showToast(e.message, 'error'); }
    },

    render: function() {
        const list = document.getElementById('cert-list');
        list.innerHTML = this.items.map(i => `
            <div style="padding:10px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between;">
                <span>${i.nombre}</span>
                <strong>${i.cant}</strong>
            </div>
        `).reverse().join('');
    },

    finish: async function() {
        if(!confirm('¿Desea finalizar la certificación?')) return;
        try {
            const res = await window.api.post(`/certificaciones/${this.currentCert}/end`);
            window.showToast('Certificación finalizada. Diferencias: ' + (res.diferencias ? 'SÍ' : 'NO'), res.diferencias ? 'warning' : 'success');
            window.goToHome();
        } catch(e) { window.showToast(e.message, 'error'); }
    }
};
