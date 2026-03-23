/**
 * Prooriente WMS - Main App Logic
 */

document.addEventListener('DOMContentLoaded', () => {

    // 1. Service Worker Registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js')
                .then(reg => console.log('SW Registrado:', reg.scope))
                .catch(err => console.error('Error registrando SW:', err));
        });
    }

    // 2. App Initialization after Login
    window.addEventListener('app:ready', () => {
        initDashboard();
    });

    // 3. Logout handling
    document.getElementById('btn-logout').addEventListener('click', () => {
        window.api.clearAuth();
        window.location.reload();
    });

    // ----- Dynamic Module Render -----
    function initDashboard() {
        const permsStr = localStorage.getItem('user_permissions');
        let permissions = [];
        try {
            permissions = permsStr ? JSON.parse(permsStr) : [];
        } catch (e) {}

        const container = document.getElementById('modules-container');
        if (!container) return;
        container.innerHTML = ''; 

        const availableModules = [
            { id: 'recepcion', name: 'Recepción', icon: 'fa-truck-ramp-box', reqPerm: 'recepcion', colorClass: 'color-inbound' },
            { id: 'almacenamiento', name: 'Almacenar', icon: 'fa-dolly', reqPerm: 'almacenamiento', colorClass: 'color-almacen' },
            { id: 'inventario', name: 'Conteo & Inv', icon: 'fa-boxes-packing', reqPerm: 'inventario', colorClass: 'color-inventory' },
            { id: 'picking', name: 'Picking', icon: 'fa-cart-flatbed', reqPerm: 'picking', colorClass: 'color-picking' },
            { id: 'despacho', name: 'Despacho', icon: 'fa-truck-fast', reqPerm: 'despacho', colorClass: 'color-outbound' },
            { id: 'devoluciones', name: 'Devoluciones', icon: 'fa-rotate-left', reqPerm: 'recepcion', colorClass: 'color-return' },
            { id: 'maestros', name: 'Maestros', icon: 'fa-database', reqPerm: 'admin', colorClass: 'color-admin' }
        ];

        let added = 0;

        // Admin override check (if permissions list contains *.* or user role is admin)
        const userDataStr = localStorage.getItem('user_data');
        const user = userDataStr ? JSON.parse(userDataStr) : null;
        const isAdmin = permissions.some(p => p === '*.*' || (p.modulo === '*' && p.accion === '*')) || (user && user.rol.toLowerCase() === 'admin');

        availableModules.forEach(mod => {
            const hasAccess = isAdmin || permissions.includes(`${mod.reqPerm}.ver`);
            
            if (hasAccess) {
                added++;
                const card = document.createElement('div');
                card.className = 'module-card card-main fade-in';
                card.style.animationDelay = `${added * 0.05}s`;
                card.innerHTML = `
                    <div class="module-icon-wrap ${mod.colorClass}">
                        <i class="fa-solid ${mod.icon}"></i>
                    </div>
                    <h3 class="module-title">${mod.name}</h3>
                `;
                card.addEventListener('click', () => {
                    if (navigator.vibrate) navigator.vibrate(20);
                    openView(mod.id, mod.name);
                });
                container.appendChild(card);
            }
        });

        if (added === 0) {
            container.innerHTML = `<p style="color: #64748b; text-align: center; grid-column: span 2;">No tienes permisos asignados.</p>`;
        }
    }

    // Wiring Bottom Nav
    window.goToHome = function() {
        if(window.closeView) {
            window.closeView('view-level-2');
            setTimeout(() => { window.closeView('view-level-1'); }, 100);
        }
        document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
        if(document.querySelector('.nav-item')) document.querySelector('.nav-item').classList.add('active'); 
        if (navigator.vibrate) navigator.vibrate([10, 30, 10]);
    };

    window.openAlertas = function() {
        const items = document.querySelectorAll('.nav-item');
        items.forEach(el => el.classList.remove('active'));
        if(items[1]) items[1].classList.add('active');
        
        openView('alertas', 'Centro de Notificaciones');
    };

    window.openAjustes = function() {
        const items = document.querySelectorAll('.nav-item');
        items.forEach(el => el.classList.remove('active'));
        if(items[2]) items[2].classList.add('active');
        
        openView('ajustes', 'Configuración del Sistema');
    };

    // ----- App Shell View Navigation System -----
    const viewContainer = document.getElementById('view-container');
    
    // Submenu Configurations
    const subMenus = {
        'maestros': [
            { id: 'empresas', title: 'Empresas', icon: 'fa-building', colorClass: 'color-admin' },
            { id: 'sucursales', title: 'Sucursales', icon: 'fa-warehouse', colorClass: 'color-picking' },
            { id: 'marcas', title: 'Marcas', icon: 'fa-tags', colorClass: 'color-inventory' },
            { id: 'productos', title: 'Productos', icon: 'fa-box', colorClass: 'color-inbound' },
            { id: 'personal', title: 'Personal', icon: 'fa-users', colorClass: 'color-outbound' },
            { id: 'clientes', title: 'Clientes', icon: 'fa-users-rectangle', colorClass: 'color-inbound' },
            { id: 'ubicaciones', title: 'Ubicaciones', icon: 'fa-map-location-dot', colorClass: 'color-almacen' },
            { id: 'proveedores', title: 'Proveedores', icon: 'fa-truck-field', colorClass: 'color-picking' },
            { id: 'rutas', title: 'Rutas', icon: 'fa-route', colorClass: 'color-inbound' }
        ],
        'recepcion': [
            { id: 'citas', title: 'Gestión de Citas', icon: 'fa-calendar-check', colorClass: 'color-inbound' },
            { id: 'odc', title: 'Orden de Compra', icon: 'fa-file-invoice', colorClass: 'color-admin' },
            { id: 'recepcion_nueva', title: 'Nueva Recepción', icon: 'fa-boxes-packing', colorClass: 'color-inbound' }
        ],
        'almacenamiento': [
            { id: 'putaway', title: 'Putaway (Acomodo)', icon: 'fa-pallet', colorClass: 'color-almacen' },
            { id: 'traslado', title: 'Traslado Interno', icon: 'fa-people-carry-box', colorClass: 'color-almacen' }
        ],
        'picking': [
            { id: 'picking_rutas', title: 'Rutas Pendientes', icon: 'fa-route', colorClass: 'color-picking' }
        ],
        'despacho': [
            { id: 'certificacion_consolidada', title: 'Certif. Consolidada', icon: 'fa-cubes-stacked', colorClass: 'color-outbound' },
            { id: 'certificacion_detalle', title: 'Certif. por Cliente', icon: 'fa-clipboard-user', colorClass: 'color-inventory' }
        ],
        'ajustes': [
            { id: 'permisos', title: 'Gestión de Permisos', icon: 'fa-shield-halved', colorClass: 'color-admin' },
            { id: 'mi_perfil', title: 'Mi Perfil', icon: 'fa-user-gear', colorClass: 'color-almacen' },
            { id: 'empresa_config', title: 'Datos Empresa', icon: 'fa-hotel', colorClass: 'color-picking' }
        ],
        'inventario': [
            { id: 'conteo_nuevo', title: 'Nuevo Conteo', icon: 'fa-clipboard-list', colorClass: 'color-inventory' },
            { id: 'conteos_historial', title: 'Historial Conteos', icon: 'fa-clock-rotate-left', colorClass: 'color-inventory' }
        ],
        'devoluciones': [
            { id: 'recepcion_devolucion', title: 'Nueva Devolución', icon: 'fa-rotate-left', colorClass: 'color-return' }
        ],
        'alertas': [] // Specialized render
    };

    window.openView = function(viewId, viewName) {
        if(!viewContainer) return;

        let contentHtml = '';

        if (subMenus[viewId]) {
            // Render specific Submenu Grid identically to main dashboard
            let cardsHtml = '';
            subMenus[viewId].forEach((sub, idx) => {
                cardsHtml += `
                    <div class="module-card card-sub fade-in" style="animation-delay: ${idx * 0.05}s;" onclick="openSubView('${sub.id}', '${sub.title}')">
                        <div class="module-icon-wrap ${sub.colorClass}">
                            <i class="fa-solid ${sub.icon}"></i>
                        </div>
                        <h3 class="module-title">${sub.title}</h3>
                    </div>
                `;
            });
            contentHtml = `<div class="module-grid">${cardsHtml}</div>`;
        } else if (viewId === 'alertas') {
            contentHtml = `
                <div style="padding:10px;">
                    <div style="background:white; border-radius:12px; padding:15px; border-left:4px solid #ef4444; margin-bottom:12px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <strong style="color:#0f172a;">Stock Crítico</strong>
                            <small style="color:#64748b;">Hace 5 min</small>
                        </div>
                        <p style="margin:5px 0 0; font-size:0.85rem; color:#475569;">El producto "Aceite de Palma" está por debajo del stock mínimo en Bodega Principal.</p>
                    </div>
                    <div style="background:white; border-radius:12px; padding:15px; border-left:4px solid #f59e0b; margin-bottom:12px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <strong style="color:#0f172a;">Cita Retrasada</strong>
                            <small style="color:#64748b;">Hace 1 hora</small>
                        </div>
                        <p style="margin:5px 0 0; font-size:0.85rem; color:#475569;">El proveedor "Distribuidora S.A." no ha llegado para su cita de las 08:00 AM.</p>
                    </div>
                </div>
            `;
        } else {
            // Generic construction notice
            contentHtml = `<div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                <i class="fa-solid fa-person-digging" style="font-size:3rem; margin-bottom:16px; color:#cbd5e1;"></i>
                <h4>El módulo ${viewName} se está construyendo...</h4>
            </div>`;
        }

        const viewHtml = `
            <div class="view-panel active" id="view-level-1">
                <div class="view-header">
                    <button class="btn-back" onclick="closeView('view-level-1')"><i class="fa-solid fa-arrow-left"></i></button>
                    <h3>${viewName}</h3>
                </div>
                <div class="view-content">
                    ${contentHtml}
                </div>
            </div>
            <!-- Container for level 2 views (CRUDs) -->
            <div id="subview-container"></div>
        `;

        viewContainer.innerHTML = viewHtml;
    }

    window.closeView = function(elementId) {
        if (navigator.vibrate) navigator.vibrate(10);
        const panel = document.getElementById(elementId);
        if (panel) {
            panel.classList.remove('active');
            setTimeout(() => { panel.remove(); }, 300);
        }
    }

    // --- Level 2 View Logic (Action / CRUD level) ---
    window.openSubView = function(subId, subTitle) {
        const subviewContainer = document.getElementById('subview-container');
        if (!subviewContainer) return;

        if (navigator.vibrate) navigator.vibrate(20);

        let contentHtml = '';

        // Master parameterization routing
        if (subId === 'empresas' && window.Maestros) {
            contentHtml = window.Maestros.getEmpresasHTML();
            setTimeout(() => { window.Maestros.loadEmpresas(); }, 400); 
        } else if (subId === 'sucursales' && window.Maestros) {
            contentHtml = window.Maestros.getSucursalesHTML();
            setTimeout(() => { window.Maestros.loadSucursales(); }, 400);
        } else if (subId === 'marcas' && window.Maestros) {
            contentHtml = window.Maestros.getMarcasHTML();
            setTimeout(() => { window.Maestros.loadMarcas(); }, 400);
        } else if (subId === 'productos' && window.Maestros) {
            contentHtml = window.Maestros.getProductosHTML();
            setTimeout(() => { window.Maestros.loadProductos(); }, 400);
        } else if (subId === 'personal' && window.Maestros) {
            contentHtml = window.Maestros.getPersonalHTML();
            setTimeout(() => { window.Maestros.loadPersonal(); }, 400);
        } else if (subId === 'clientes' && window.Maestros) {
            contentHtml = window.Maestros.getClientesHTML();
            setTimeout(() => { window.Maestros.loadClientes(); }, 400);
        } else if (subId === 'ubicaciones' && window.Maestros) {
            contentHtml = window.Maestros.getUbicacionesHTML();
            setTimeout(() => { window.Maestros.loadUbicaciones(); }, 400);
        } else if (subId === 'proveedores' && window.Maestros) {
            contentHtml = window.Maestros.getProveedoresHTML();
            setTimeout(() => { window.Maestros.loadProveedores(); }, 400);
        } else if (subId === 'rutas' && window.Maestros) {
            contentHtml = window.Maestros.getRutasHTML();
            setTimeout(() => { window.Maestros.loadRutas(); }, 400);
        } else if (subId === 'odc' && window.ODC) {
            contentHtml = window.ODC.getODCHTML();
            setTimeout(() => { window.ODC.loadODCs(); }, 400);
        } else if (subId === 'certificacion_consolidada' && window.Certificacion) {
            contentHtml = window.Certificacion.getCertificacionHTML('Consolidado');
        } else if (subId === 'certificacion_detalle' && window.Certificacion) {
            contentHtml = window.Certificacion.getCertificacionHTML('Detalle');
        } else if (subId === 'conteo_nuevo' && window.Inventario) {
            contentHtml = window.Inventario.getNuevoConteoHTML();
            setTimeout(() => { window.Inventario.initNuevoConteo(); }, 400);
        } else if (subId === 'conteos_historial' && window.Inventario) {
            contentHtml = window.Inventario.getHistorialConteosHTML();
            setTimeout(() => { window.Inventario.loadHistorialConteos(); }, 400);
        } else if (subId === 'permisos' && window.Permisos) {
            contentHtml = window.Permisos.getPermisosHTML();
            setTimeout(() => { window.Permisos.init(); }, 100);
        } else if (subId === 'mi_perfil' && window.Ajustes) {
            contentHtml = window.Ajustes.getProfileHTML();
        } else if (subId === 'empresa_config' && window.Ajustes) {
            contentHtml = window.Ajustes.getCompanyConfigHTML();
        } else if (subId === 'citas' && window.Recepcion) {
            contentHtml = window.Recepcion.getCitasHTML();
            setTimeout(() => { window.Recepcion.loadCitas(); }, 400);
        } else if (subId === 'recepcion_nueva' && window.Recepcion) {
            contentHtml = window.Recepcion.getRecepcionNuevaHTML();
        } else if (subId === 'putaway' && window.Almacenamiento) {
            contentHtml = window.Almacenamiento.getPutawayHTML();
        } else if (subId === 'traslado' && window.Almacenamiento) {
            contentHtml = window.Almacenamiento.getTrasladoHTML();
        } else if (subId === 'picking_rutas' && window.Picking) {
            contentHtml = window.Picking.getPickingRutasHTML();
            setTimeout(() => { window.Picking.loadPickingRutas(); }, 400);
        } else if (subId === 'certificacion' && window.Certificacion) {
            contentHtml = window.Certificacion.getCertificacionHTML('Consolidado');
        } else if (subId === 'recepcion_devolucion' && window.Devoluciones) {
             contentHtml = window.Devoluciones.getDevolucionesHTML();
        // Block removed (duplicated)
        /* } else if (subId === 'permisos' && window.Permisos) {
             contentHtml = window.Permisos.getPermisosHTML();
             setTimeout(() => { window.Permisos.init(); }, 100); */
        } else {
             contentHtml = `<div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                <i class="fa-solid fa-hammer" style="font-size:3rem; margin-bottom:16px; color:#cbd5e1;"></i>
                <h4>Funcionalidad '${subTitle}' pronto</h4>
            </div>`;
        }

        const viewHtml = `
            <div class="view-panel active" id="view-level-2" style="z-index:60;">
                <div class="view-header">
                    <button class="btn-back" onclick="closeView('view-level-2')"><i class="fa-solid fa-arrow-left"></i></button>
                    <h3>${subTitle}</h3>
                </div>
                <div class="view-content" style="background:#f8fafc;">
                    ${contentHtml}
                </div>
            </div>
        `;
        subviewContainer.innerHTML = viewHtml;
    }
});
