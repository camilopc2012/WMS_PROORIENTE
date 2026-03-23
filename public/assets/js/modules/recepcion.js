/**
 * Prooriente WMS - Recepción Module
 */
window.Recepcion = {
    currentRecepcion: null,

    /* --- GESTIÓN DE CITAS --- */
    getCitasHTML: function() {
        return `
            <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0; margin-bottom: 20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <h4 style="margin:0; color:#0f172a;">Agendamiento de Citas</h4>
                    <button class="btn-primary" style="padding:8px 16px; width:auto; border-radius:8px; font-size:0.9rem;" onclick="window.Recepcion.showCitaForm()"><i class="fa-solid fa-calendar-plus"></i> Nueva Cita</button>
                </div>
                ${filterBarHTML('citas-tbody', '🔍 Buscar por proveedor, ODC o estado...')}
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem;">
                        <thead>
                            <tr style="border-bottom:2px solid #e2e8f0; color:#64748b;">
                                <th style="padding:10px 8px;">Fecha/Hora</th>
                                <th style="padding:10px 8px;">Proveedor</th>
                                <th style="padding:10px 8px;">ODC</th>
                                <th style="padding:10px 8px;">Estado</th>
                                <th style="padding:10px 8px; width:100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="citas-tbody">
                            <tr><td colspan="5" style="text-align:center; padding:20px; color:#94a3b8;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando citas...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Template -->
            <div id="form-cita-container" style="display:none; background:white; border-radius:12px; padding:25px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); border:1px solid #e2e8f0; max-width:600px; margin:0 auto 30px;">
                <h4 style="margin-top:0; color:#0f172a; margin-bottom: 20px;">Programar Cita</h4>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="input-group" style="grid-column: span 2;">
                        <label>Proveedor *</label>
                        <input type="text" id="cita-prov" class="input-field" placeholder="Nombre del proveedor">
                    </div>
                    <div class="input-group">
                        <label>Fecha *</label>
                        <input type="date" id="cita-fecha" class="input-field" onchange="window.Recepcion.checkDisponibilidad()">
                    </div>
                    <div class="input-group">
                        <label>Hora *</label>
                        <input type="time" id="cita-hora" class="input-field">
                        <div id="cita-info-cupos" style="font-size:0.7rem; color:#6366f1; margin-top:4px;">Seleccione una fecha para ver disponibilidad</div>
                    </div>
                    <div class="input-group">
                        <label>Tipo de Carro / Vehículo</label>
                        <input type="text" id="cita-tipo-carro" class="input-field" placeholder="Ej: Camión NHR, Turbo...">
                    </div>
                    <div class="input-group">
                        <label>Peso Estimado (Kilos)</label>
                        <input type="number" id="cita-peso" class="input-field" placeholder="0.00">
                    </div>
                    <div class="input-group">
                        <label>Orden de Compra</label>
                        <input type="text" id="cita-odc" class="input-field" placeholder="ODC-123">
                    </div>
                    <div class="input-group">
                        <label>Cant. Cajas (Est.)</label>
                        <input type="number" id="cita-cajas" class="input-field" placeholder="0">
                    </div>
                </div>
                <div style="display:flex; gap:10px; margin-top:20px;">
                    <button class="btn-primary" style="flex:1;" onclick="window.Recepcion.saveCita()">Guardar</button>
                    <button class="btn-primary" style="flex:1; background:#cbd5e1; color:#334155;" onclick="document.getElementById('form-cita-container').style.display='none'">Cancelar</button>
                </div>
            </div>
        `;
    },

    loadCitas: async function() {
        const tbody = document.getElementById('citas-tbody');
        if(!tbody) return;
        try {
            const res = await window.api.get('/citas');
            let html = '';
            res.data.forEach(c => {
                const badgeColor = c.estado === 'Programada' ? '#3b82f6' : (c.estado === 'EnCurso' ? '#f59e0b' : '#10b981');
                html += `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px 8px;">
                            <div style="font-weight:600;">${c.fecha}</div>
                            <div style="font-size:0.75rem; color:#64748b;">${c.hora_programada}</div>
                        </td>
                        <td style="padding:12px 8px; color:#475569;">${c.proveedor}</td>
                        <td style="padding:12px 8px; font-family:monospace;">${c.odc || '-'}</td>
                        <td style="padding:12px 8px;"><span style="color:white; background:${badgeColor}; padding:2px 8px; border-radius:10px; font-size:0.75rem;">${c.estado}</span></td>
                        <td style="padding:12px 8px; text-align:right;">
                            ${c.estado === 'Programada' ? `<button onclick="window.Recepcion.iniciarDesdeCita(${c.id})" class="btn-primary" style="padding:4px 8px; font-size:0.7rem; width:auto; background:#10b981;">Recibir</button>` : ''}
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html || '<tr><td colspan="5" style="text-align:center; padding:20px;">No hay citas pendientes.</td></tr>';
        } catch(e) {}
    },

    showCitaForm: function() {
        document.getElementById('form-cita-container').style.display = 'block';
    },

    saveCita: async function() {
        const payload = {
            proveedor: document.getElementById('cita-prov').value,
            fecha: document.getElementById('cita-fecha').value,
            hora_programada: document.getElementById('cita-hora').value,
            odc: document.getElementById('cita-odc').value,
            cantidad_cajas: document.getElementById('cita-cajas').value,
            tipo_vehiculo: document.getElementById('cita-tipo-carro').value,
            kilos: document.getElementById('cita-peso').value
        };
        if(!payload.proveedor || !payload.fecha || !payload.hora_programada) {
            return window.showToast('Proveedor, Fecha y Hora son requeridos', 'error');
        }
        try {
            await window.api.post('/citas', payload);
            window.showToast('Cita agendada');
            document.getElementById('form-cita-container').style.display = 'none';
            this.loadCitas();
        } catch(e) { window.showToast(e.message, 'error'); }
    },

    checkDisponibilidad: async function() {
        const fecha = document.getElementById('cita-fecha').value;
        if(!fecha) return;
        const info = document.getElementById('cita-info-cupos');
        info.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Consultando...';
        try {
            const res = await window.api.get(`/citas/disponibilidad?fecha=${fecha}`);
            if(res.ocupacion && res.ocupacion.length > 0) {
                let txt = 'Horas con cupos ocupados: ';
                res.ocupacion.forEach(o => {
                    txt += `${o.hora_programada} (${o.total}/${res.max_por_hora}), `;
                });
                info.innerText = txt.slice(0, -2);
                info.style.color = '#ef4444';
            } else {
                info.innerText = 'Toda la fecha está disponible (máx ' + res.max_por_hora + ' por hora)';
                info.style.color = '#10b981';
            }
        } catch(e) {
            info.innerText = 'Error al consultar disponibilidad';
        }
    },

    /* --- OPERACIÓN DE RECEPCIÓN --- */
    getRecepcionNuevaHTML: function() {
        return `
            <div style="max-width:800px; margin:0 auto;">
                <div id="recepcion-setup" style="background:white; border-radius:12px; padding:25px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0; text-align:center;">
                    <i class="fa-solid fa-boxes-packing" style="font-size:3rem; color:#6366f1; margin-bottom:20px;"></i>
                    <h3 style="margin:0; color:#0f172a;">Nueva Recepción de Mercancía</h3>
                    <p style="color:#64748b; margin-bottom:30px;">Inicie una descarga manual o vincule a una cita existente</p>
                    
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        <button class="btn-primary" style="height:auto; padding:25px; background:#f8fafc; color:#334155; border:1px solid #e2e8f0;" onclick="window.Recepcion.iniciarNueva(true)">
                            <i class="fa-solid fa-eye-slash" style="display:block; font-size:1.5rem; margin-bottom:10px; color:#6366f1;"></i>
                            <strong>Modo Ciego</strong>
                            <div style="font-size:0.75rem; color:#64748b; font-weight:400; margin-top:5px;">Contar sin saber lo esperado (más precisión)</div>
                        </button>
                        <button class="btn-primary" style="height:auto; padding:25px; background:#f8fafc; color:#334155; border:1px solid #e2e8f0;" onclick="window.openSubView('citas', 'Gestión de Citas')">
                            <i class="fa-solid fa-calendar-check" style="display:block; font-size:1.5rem; margin-bottom:10px; color:#10b981;"></i>
                            <strong>Vincular Cita</strong>
                            <div style="font-size:0.75rem; color:#64748b; font-weight:400; margin-top:5px;">Verificar contra orden de compra / proveedor</div>
                        </button>
                    </div>
                </div>

                <div id="recepcion-active" style="display:none; background:white; border-radius:12px; padding:20px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); border:1px solid #e2e8f0;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #f1f5f9; padding-bottom:15px;">
                        <div>
                            <span id="label-n-recep" style="font-size:0.75rem; color:#64748b; text-transform:uppercase;">Recepción</span>
                            <h4 id="active-recepcion-num" style="margin:0; color:#0f172a;">RC-Loading...</h4>
                        </div>
                        <button class="btn-primary" style="background:#ef4444; width:auto; font-size:0.8rem;" onclick="window.Recepcion.confirmarFinal()">Finalizar Descarga</button>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">
                        <div class="input-group" style="grid-column: span 2;">
                           <label>Escanear Producto (EAN/Código)</label>
                           <div style="display:flex; gap:10px;">
                               <input type="text" id="recep-scan" class="input-field" placeholder="Busque o escanee...">
                               <button class="btn-primary" style="width:50px;" onclick="window.Recepcion.buscarProducto()"><i class="fa-solid fa-search"></i></button>
                           </div>
                        </div>
                        <div class="input-group">
                            <label>Cantidad</label>
                            <input type="number" id="recep-cant" class="input-field" value="1">
                        </div>
                        <div class="input-group">
                            <label>Lote</label>
                            <input type="text" id="recep-lote" class="input-field" placeholder="LOTE123">
                        </div>
                        <div class="input-group">
                            <label>Vencimiento</label>
                            <input type="date" id="recep-vence" class="input-field">
                        </div>
                         <div class="input-group">
                            <label>Estado</label>
                            <select id="recep-estado" class="input-field">
                                <option value="BuenEstado">Buen Estado</option>
                                <option value="Averia">Avería</option>
                                <option value="Cuarentena">Cuarentena</option>
                            </select>
                        </div>
                        <button class="btn-primary" style="grid-column: span 2; background:#0f172a;" onclick="window.Recepcion.agregarLinea()">Agregar a Descarga</button>
                    </div>

                    <div style="border-top:1px solid #f1f5f9; padding-top:15px;">
                        <h5 style="margin:0 0 10px 0; color:#475569;">Resumen de Items Recibidos</h5>
                        <div id="recep-detalles-list" style="max-height:300px; overflow-y:auto; border:1px solid #f1f5f9; border-radius:8px;">
                            <div style="padding:20px; text-align:center; color:#94a3b8; font-size:0.85rem;">Lista vacía</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    iniciarNueva: async function(ciego, citaId = null) {
        try {
            const res = await window.api.post('/recepciones', { modo_ciego: ciego, cita_id: citaId });
            this.currentRecepcion = res.data;
            document.getElementById('recepcion-setup').style.display = 'none';
            document.getElementById('recepcion-active').style.display = 'block';
            document.getElementById('active-recepcion-num').innerText = res.data.numero_recepcion;
        } catch(e) { window.showToast(e.message, 'error'); }
    },

    iniciarDesdeCita: function(id) {
        this.iniciarNueva(false, id);
    },

    agregarLinea: async function() {
        if(!this.currentRecepcion) return;
        
        const payload = {
            producto_id: this._lastProductoId, // This should be set by search/scan
            cantidad_recibida: document.getElementById('recep-cant').value,
            lote: document.getElementById('recep-lote').value,
            fecha_vencimiento: document.getElementById('recep-vence').value,
            estado_mercancia: document.getElementById('recep-estado').value
        };

        if(!payload.producto_id) return window.showToast('Primero busque un producto', 'error');

        try {
            await window.api.post(`/recepciones/${this.currentRecepcion.id}/detalle`, payload);
            window.showToast('Item agregado');
            this.renderResumen();
            // Clear fields
            document.getElementById('recep-scan').value = '';
            document.getElementById('recep-cant').value = '1';
            this._lastProductoId = null;
        } catch(e) { window.showToast(e.message, 'error'); }
    },

    buscarProducto: async function() {
        const query = document.getElementById('recep-scan').value;
        if(query.length < 3) return;
        try {
            const res = await window.api.get('/param/productos');
            // Mock search in all products
            const p = res.data.find(x => x.codigo_interno === query || x.ean13 === query || x.nombre.toLowerCase().includes(query.toLowerCase()));
            if(p) {
                this._lastProductoId = p.id;
                window.showToast('Producto: ' + p.nombre, 'success');
                document.getElementById('recep-scan').value = p.nombre;
                if(p.controla_vencimiento) document.getElementById('recep-vence').focus();
                else document.getElementById('recep-lote').focus();
            } else {
                window.showToast('Producto no encontrado', 'error');
            }
        } catch(e) {}
    },

    renderResumen: async function() {
        // Here we could fetch the details from backend or keep local. Let's assume backend for consistency.
        // For brevity in this script, we'll just show a success message or mock list.
    },

    confirmarFinal: async function() {
        if(!confirm('¿Seguro que desea cerrar la recepción? Se actualizará el inventario.')) return;
        try {
            await window.api.post(`/recepciones/${this.currentRecepcion.id}/confirm`);
            window.showToast('Recepción completada e Inventario actualizado', 'success');
            window.goToHome();
        } catch(e) { window.showToast(e.message, 'error'); }
    }
};
