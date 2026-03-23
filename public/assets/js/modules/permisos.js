/**
 * Prooriente WMS - Permissions management
 */
window.Permisos = {
    selectedRol: null,

    getPermisosHTML: function() {
        return `
            <div style="display:grid; grid-template-columns: 250px 1fr; gap:20px; height: calc(100vh - 160px);">
                <!-- Roles Sidebar -->
                <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0;">
                    <h4 style="margin:0 0 15px 0; color:#0f172a; font-size:1rem;">Roles del Sistema</h4>
                    <div id="roles-list-container" style="display:flex; flex-direction:column; gap:8px;">
                        <div style="text-align:center; padding:20px; color:#94a3b8;"><i class="fa-solid fa-spinner fa-spin"></i></div>
                    </div>
                </div>

                <!-- Permissions Matrix -->
                <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0; overflow-y:auto;">
                    <div id="matrix-header" style="margin-bottom:20px; display:none;">
                        <h4 id="matrix-title" style="margin:0; color:#0f172a;">Permisos para: <span id="selected-rol-name" style="color:#ef4444;"></span></h4>
                        <p style="color:#64748b; font-size:0.85rem; margin-top:4px;">Los cambios se guardan automáticamente al marcar/desmarcar.</p>
                    </div>

                    <div id="matrix-empty" style="text-align:center; padding:60px 20px; color:#94a3b8;">
                        <i class="fa-solid fa-user-shield" style="font-size:3rem; margin-bottom:16px; color:#cbd5e1;"></i>
                        <p>Seleccione un rol de la izquierda para gestionar sus permisos.</p>
                    </div>

                    <div id="matrix-table-container" style="display:none;">
                        <table style="width:100%; border-collapse:collapse; text-align:left; font-size:0.9rem;">
                            <thead>
                                <tr style="border-bottom:2px solid #e2e8f0; color:#64748b;">
                                    <th style="padding:12px 8px;">Módulo</th>
                                    <th style="padding:12px 8px;">Acción</th>
                                    <th style="padding:12px 8px;">Descripción</th>
                                    <th style="padding:12px 8px; width:80px; text-align:center;">Acceso</th>
                                </tr>
                            </thead>
                            <tbody id="permisos-matrix-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    },

    init: async function() {
        await this.loadRoles();
    },

    loadRoles: async function() {
        const container = document.getElementById('roles-list-container');
        if(!container) return;
        try {
            const res = await window.api.get('/param/roles');
            const roles = res.data || [];
            let html = '';
            roles.forEach(rol => {
                html += `
                    <button class="role-item-btn" onclick="window.Permisos.selectRol('${rol.id}', '${rol.nombre}')" 
                        style="text-align:left; padding:12px 15px; border-radius:8px; border:1px solid #f1f5f9; background:#f8fafc; color:#475569; cursor:pointer; font-size:0.9rem; transition:all 0.2s; font-weight:500;">
                        <i class="fa-solid fa-user-tag" style="margin-right:10px; color:#94a3b8;"></i> ${rol.nombre}
                    </button>
                `;
            });
            container.innerHTML = html;
        } catch(e) {
            container.innerHTML = `<div style="color:#ef4444; font-size:0.8rem;">Error al cargar roles</div>`;
        }
    },

    selectRol: async function(rolId, rolNombre) {
        this.selectedRol = rolId;
        
        // UI Feedback for selection
        document.querySelectorAll('.role-item-btn').forEach(btn => {
            if(btn.innerText.includes(rolNombre)) {
                btn.style.background = '#ef4444';
                btn.style.color = 'white';
                btn.style.borderColor = '#ef4444';
                btn.querySelector('i').style.color = 'white';
            } else {
                btn.style.background = '#f8fafc';
                btn.style.color = '#475569';
                btn.style.borderColor = '#f1f5f9';
                btn.querySelector('i').style.color = '#94a3b8';
            }
        });

        document.getElementById('matrix-empty').style.display = 'none';
        document.getElementById('matrix-header').style.display = 'block';
        document.getElementById('matrix-table-container').style.display = 'block';
        document.getElementById('selected-rol-name').innerText = rolNombre;

        const tbody = document.getElementById('permisos-matrix-tbody');
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:30px;"><i class="fa-solid fa-spinner fa-spin"></i> Cargando matriz...</td></tr>';

        try {
            const res = await window.api.get(`/param/permisos-matriz/${rolId}`);
            const matrix = res.data || [];
            let html = '';
            let currentModulo = '';

            matrix.forEach(p => {
                if(p.modulo !== currentModulo) {
                    html += `<tr style="background:#f8fafc;"><td colspan="4" style="padding:8px 12px; font-weight:700; color:#0f172a; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px;">${p.modulo}</td></tr>`;
                    currentModulo = p.modulo;
                }
                html += `
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:12px 15px; color:#94a3b8; font-size:0.8rem;">${p.modulo}</td>
                        <td style="padding:12px 8px; font-weight:600; color:#1e293b;">${p.accion}</td>
                        <td style="padding:12px 8px; color:#64748b; font-size:0.85rem;">${p.descripcion}</td>
                        <td style="padding:12px 8px; text-align:center;">
                            <label class="switch-permiso">
                                <input type="checkbox" ${p.concedido ? 'checked' : ''} onchange="window.Permisos.togglePermiso(${p.id}, this.checked)">
                                <span class="slider-permiso"></span>
                            </label>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        } catch(e) {
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:#ef4444; padding:20px;">Error: ${e.message}</td></tr>`;
        }
    },

    togglePermiso: async function(permisoId, concedido) {
        if(!this.selectedRol) return;
        try {
            await window.api.post('/param/permisos-toggle', {
                rol: this.selectedRol,
                permiso_id: permisoId,
                concedido: concedido
            });
            window.showToast('Permiso actualizado', 'success');
        } catch(e) {
            window.showToast(e.message, 'error');
        }
    }
};
