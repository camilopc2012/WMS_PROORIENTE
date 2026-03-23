/**
 * Prooriente WMS - Ajustes & Permisos Module
 */
window.Ajustes = {
    
    /* --- GESTOR DE PERMISOS --- */
    getPermisosHTML: function() {
        return `
            <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0; margin-bottom: 20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; gap:15px; flex-wrap:wrap;">
                    <div style="flex:1; min-width:200px;">
                        <h4 style="margin:0; color:#0f172a; margin-bottom:4px;">Gestor de Permisos por Rol</h4>
                        <p style="margin:0; font-size:0.85rem; color:#64748b;">Configure el acceso a módulos y acciones para cada cargo.</p>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <label style="font-weight:600; color:#475569; font-size:0.9rem;">Seleccionar Rol:</label>
                        <select id="perm-rol-select" class="input-field" style="width:200px; margin:0;" onchange="window.Ajustes.loadPermissionsMatrix()">
                            <option value="">Cargando roles...</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:14px; position:relative;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:0.85rem;"></i>
                    <input type="text" class="input-field" placeholder="🔍 Buscar por módulo, acción o descripción..." onkeyup="window.handleSmartFilter(this, 'permisos-matrix-tbody')"
                        style="padding-left:36px; border-radius:8px; height:40px; font-size:0.85rem; border:1px solid #e2e8f0; width:100%; box-sizing:border-box;">
                </div>

                <div style="overflow-x:auto; border:1px solid #f1f5f9; border-radius:8px;">
                    <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.85rem;">
                        <thead style="background:#f8fafc;">
                            <tr style="border-bottom:2px solid #e2e8f0; color:#475569;">
                                <th style="padding:12px 15px; width:150px;">Módulo</th>
                                <th style="padding:12px 15px;">Acción / Permiso</th>
                                <th style="padding:12px 15px;">Descripción</th>
                                <th style="padding:12px 15px; text-align:center; width:100px;">Concedido</th>
                            </tr>
                        </thead>
                        <tbody id="permisos-matrix-tbody">
                            <tr><td colspan="4" style="text-align:center; padding:40px; color:#94a3b8;"><i class="fa-solid fa-shield-halved fa-beat" style="font-size:2rem; margin-bottom:10px;"></i><br>Seleccione un rol para ver la matriz</td></tr>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:20px; padding:15px; background:#f0f9ff; border-radius:8px; border:1px solid #bae6fd; display:flex; gap:10px; align-items:center;">
                    <i class="fa-solid fa-circle-info" style="color:#0ea5e9;"></i>
                    <p style="margin:0; font-size:0.85rem; color:#0369a1;">Los cambios se guardan automáticamente al marcar/desmarcar cada casilla.</p>
                </div>
            </div>
        `;
    },

    loadRoles: async function() {
        const select = document.getElementById('perm-rol-select');
        if(!select) return;
        try {
            const res = await window.api.get('/param/roles');
            select.innerHTML = '<option value="">-- Seleccione un Rol --</option>';
            res.data.forEach(r => {
                select.innerHTML += `<option value="${r.id}">${r.nombre}</option>`;
            });
        } catch(e) { console.error(e); }
    },

    loadPermissionsMatrix: async function() {
        const rol = document.getElementById('perm-rol-select').value;
        const tbody = document.getElementById('permisos-matrix-tbody');
        if(!rol) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:40px; color:#94a3b8;">Seleccione un rol para continuar</td></tr>';
            return;
        }

        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:40px;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando matriz de permisos...</td></tr>';
        
        try {
            const res = await window.api.get(`/param/permisos-matriz/${rol}`);
            let html = '';
            let currentModulo = '';

            res.data.forEach(p => {
                const sameModulo = p.modulo === currentModulo;
                html += `<tr style="border-bottom:1px solid #f1f5f9; ${!sameModulo ? 'border-top:2px solid #f1f5f9;' : ''}">
                    <td style="padding:12px 15px; font-weight:700; color:#0f172a; text-transform:capitalize;">${sameModulo ? '' : p.modulo}</td>
                    <td style="padding:12px 15px;"><span style="background:#f1f5f9; padding:4px 8px; border-radius:6px; font-size:0.8rem; font-weight:600;">${p.accion}</span></td>
                    <td style="padding:12px 15px; color:#64748b;">${p.descripcion}</td>
                    <td style="padding:12px 15px; text-align:center;">
                        <input type="checkbox" ${p.concedido ? 'checked' : ''} 
                            style="width:20px; height:20px; cursor:pointer;" 
                            onchange="window.Ajustes.togglePermiso('${rol}', ${p.id}, this.checked)">
                    </td>
                </tr>`;
                currentModulo = p.modulo;
            });
            tbody.innerHTML = html || '<tr><td colspan="4" style="text-align:center; padding:20px;">No se encontraron permisos definidos.</td></tr>';
        } catch(e) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:20px; color:red;">Error: ${e.message}</td></tr>`;
        }
    },

    togglePermiso: async function(rol, permisoId, concedido) {
        try {
            await window.api.post('/param/permisos-toggle', {
                rol: rol,
                permiso_id: permisoId,
                concedido: concedido
            });
            window.showToast('Permiso actualizado');
            // We don't reload matrix to keep scroll position and UX
        } catch (e) {
            window.showToast('Error al actualizar permiso', 'error');
            // Revert checkbox if failed? maybe too complex for simple UI
        }
    },

    /* --- PROFILE & COMPANY DATA --- */
    getProfileHTML: function() {
        return `
            <div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                <i class="fa-solid fa-id-card-clip" style="font-size:3rem; margin-bottom:16px;"></i>
                <h4>Módulo de Perfil de Usuario</h4>
                <p>Aquí podrá cambiar su PIN y ver estadísticas personales.</p>
            </div>
        `;
    },

    getCompanyConfigHTML: function() {
        return `
            <div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                <i class="fa-solid fa-building-circle-check" style="font-size:3rem; margin-bottom:16px;"></i>
                <h4>Datos de la Empresa</h4>
                <p>Configure logo, NIT y datos legales de la empresa activa.</p>
            </div>
        `;
    }
};
