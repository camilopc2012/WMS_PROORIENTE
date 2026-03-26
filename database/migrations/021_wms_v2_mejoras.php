<?php
use Illuminate\Database\Capsule\Manager as Capsule;
return [
    'up' => function () {
        foreach ([['volumen_m3','decimal',10,4],['alto_cm','decimal',8,2],['ancho_cm','decimal',8,2],['largo_cm','decimal',8,2]] as [$c,$type,$p1,$p2]) {
            if (!Capsule::schema()->hasColumn('productos',$c)) {
                Capsule::schema()->table('productos', fn($t) => $t->$type($c,$p1,$p2)->nullable());
                echo "  ok productos.$c\n";
            }
        }
        foreach ([['capacidad_m3','decimal',10,4],['ocupado_m3','decimal',10,4],['alto_cm','decimal',8,2],['ancho_cm','decimal',8,2],['profundidad_cm','decimal',8,2],['pct_ocupacion','decimal',5,2]] as [$c,$type,$p1,$p2]) {
            if (!Capsule::schema()->hasColumn('ubicaciones',$c)) {
                Capsule::schema()->table('ubicaciones', fn($t) => $t->$type($c,$p1,$p2)->nullable());
            }
        }
        $opCols = [
            'numero_picking' => fn($t) => $t->string('numero_picking',20)->nullable(),
            'nombre_archivo' => fn($t) => $t->string('nombre_archivo',255)->nullable(),
            'estado_compromiso' => fn($t) => $t->enum('estado_compromiso',['Borrador','Comprometido','EnProceso','Completado','Cancelado'])->default('Borrador'),
            'tipo_asignacion' => fn($t) => $t->enum('tipo_asignacion',['Consolidado','PorPasillo','PorMarca','PorPlanilla'])->default('Consolidado'),
            'comprometido_at' => fn($t) => $t->timestamp('comprometido_at')->nullable(),
        ];
        foreach ($opCols as $col => $fn) {
            if (!Capsule::schema()->hasColumn('orden_pickings',$col)) Capsule::schema()->table('orden_pickings', $fn);
        }
        if (!Capsule::schema()->hasTable('picking_archivos')) {
            Capsule::schema()->create('picking_archivos', function($t) {
                $t->bigIncrements('id'); $t->unsignedBigInteger('empresa_id');
                $t->unsignedBigInteger('sucursal_id'); $t->unsignedBigInteger('orden_picking_id');
                $t->string('numero_consecutivo',20); $t->string('nombre_original',255);
                $t->integer('total_lineas')->default(0); $t->integer('total_unidades')->default(0);
                $t->enum('estado',['Pendiente','Comprometido','EnProceso','Completado'])->default('Pendiente');
                $t->unsignedBigInteger('creado_por')->nullable(); $t->timestamps();
                $t->foreign('orden_picking_id')->references('id')->on('orden_pickings')->onDelete('cascade');
            });
        }
        if (!Capsule::schema()->hasTable('picking_asignaciones')) {
            Capsule::schema()->create('picking_asignaciones', function($t) {
                $t->bigIncrements('id'); $t->unsignedBigInteger('orden_picking_id'); $t->unsignedBigInteger('auxiliar_id');
                $t->enum('criterio',['Consolidado','PorPasillo','PorMarca','PorPlanilla']);
                $t->json('pasillos')->nullable(); $t->json('marcas')->nullable(); $t->json('planillas')->nullable();
                $t->enum('estado',['Asignado','EnProceso','Completado','Reasignado'])->default('Asignado');
                $t->integer('lineas_asignadas')->default(0); $t->integer('lineas_completadas')->default(0);
                $t->timestamp('iniciado_at')->nullable(); $t->timestamps();
                $t->foreign('orden_picking_id')->references('id')->on('orden_pickings')->onDelete('cascade');
                $t->foreign('auxiliar_id')->references('id')->on('personal')->onDelete('cascade');
                $t->index(['orden_picking_id','auxiliar_id'],'idx_asig_op_aux');
            });
        }
        foreach (['asignacion_id'=>fn($t)=>$t->unsignedBigInteger('asignacion_id')->nullable(),'planilla_id'=>fn($t)=>$t->unsignedBigInteger('planilla_id')->nullable(),'marca'=>fn($t)=>$t->string('marca',100)->nullable(),'en_espera_surtido'=>fn($t)=>$t->boolean('en_espera_surtido')->default(false)] as $col=>$fn) {
            if (!Capsule::schema()->hasColumn('picking_detalles',$col)) Capsule::schema()->table('picking_detalles',$fn);
        }
        if (!Capsule::schema()->hasTable('auditoria_log')) {
            Capsule::schema()->create('auditoria_log', function($t) {
                $t->bigIncrements('id'); $t->unsignedBigInteger('empresa_id');
                $t->unsignedBigInteger('sucursal_id')->nullable(); $t->unsignedBigInteger('usuario_id')->nullable();
                $t->string('modulo',50); $t->string('accion',100); $t->string('entidad',50)->nullable();
                $t->unsignedBigInteger('entidad_id')->nullable(); $t->json('datos_antes')->nullable();
                $t->json('datos_despues')->nullable(); $t->string('ip',45)->nullable(); $t->text('observacion')->nullable();
                $t->timestamp('created_at')->useCurrent();
                $t->index(['empresa_id','modulo','created_at'],'idx_audit_mod');
            });
        }
        if (!Capsule::schema()->hasTable('consecutivos')) {
            Capsule::schema()->create('consecutivos', function($t) {
                $t->bigIncrements('id'); $t->unsignedBigInteger('empresa_id'); $t->unsignedBigInteger('sucursal_id');
                $t->string('tipo',50); $t->integer('ultimo')->default(0); $t->string('prefijo',10)->default('');
                $t->smallInteger('anio')->nullable(); $t->timestamps();
                $t->unique(['empresa_id','sucursal_id','tipo','anio'],'idx_consec');
            });
        }
        if (!Capsule::schema()->hasColumn('recepciones','impreso_at')) {
            Capsule::schema()->table('recepciones', function($t) {
                $t->timestamp('impreso_at')->nullable(); $t->unsignedBigInteger('impreso_por')->nullable();
            });
        }
        echo "  021 OK\n";
    },
    'down' => function () {
        foreach (['auditoria_log','picking_asignaciones','picking_archivos','consecutivos'] as $tbl) Capsule::schema()->dropIfExists($tbl);
    },
];