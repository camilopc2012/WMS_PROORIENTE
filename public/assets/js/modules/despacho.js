/**
 * Prooriente WMS - Despacho Module
 */
window.Despacho = {

    /* --- CERTIFICACIÓN --- */
    getCertificacionHTML: function() {
        return `
            <div style="background:white; border-radius:12px; padding:25px; box-shadow:0 1px 3px rgba(0,0,0,0.05); border:1px solid #e2e8f0; max-width:600px; margin:0 auto;">
                <div style="text-align:center; margin-bottom:24px;">
                    <div style="width:60px; height:60px; background:#fef2f2; color:#ef4444; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px; font-size:1.5rem;">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <h3 style="margin:0; color:#0f172a;">Certificación de Despacho</h3>
                    <p style="color:#64748b; font-size:0.9rem; margin-top:5px;">Auditoría final antes de despacho</p>
                </div>

                <div class="input-group">
                    <label>Seleccionar Despacho / Planilla</label>
                    <select id="desp-active-sel" class="input-field">
                        <option value="">Seleccione despacho pendiente...</option>
                        <option value="1">DSP-2023-001 (Ruta Norte)</option>
                    </select>
                </div>

                <div style="margin-top:25px; border-top:2px dashed #e2e8f0; padding-top:20px;">
                    <div class="input-group">
                        <label>Escanear Bulto / Etiqueta de Picking</label>
                        <div style="display:flex; gap:10px;">
                            <input type="text" id="cert-scan" class="input-field" placeholder="Escanee EAN o LP">
                            <button class="btn-primary" style="width:50px;"><i class="fa-solid fa-barcode"></i></button>
                        </div>
                    </div>

                    <div id="cert-resumen" style="margin-top:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:15px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:0.85rem;">
                            <span>Contabilizados:</span>
                            <strong id="cert-count">0 / 0</strong>
                        </div>
                        <div style="height:8px; background:#e2e8f0; border-radius:4px; overflow:hidden;">
                            <div id="cert-progress-bar" style="width:0%; height:100%; background:#ef4444; transition:width 0.3s;"></div>
                        </div>
                    </div>

                    <button class="btn-primary" style="background:#0f172a; margin-top:20px;" onclick="window.Despacho.cerrarDespacho()">Cerrar y Despachar</button>
                </div>
            </div>
        `;
    },

    cerrarDespacho: function() {
        if(confirm('¿Desea finalizar la certificación?')) {
            window.showToast('Despacho certificado correctamente', 'success');
            window.goToHome();
        }
    }
};
