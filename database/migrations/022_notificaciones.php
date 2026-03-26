<?php
use Illuminate\Database\Capsule\Manager as Capsule;
return [
    'up' => function () {
        if (!Capsule::schema()->hasTable('notificaciones')) {
            Capsule::schema()->create('notificaciones', function($t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('usuario_id');
                $t->string('tipo',50);
                $t->string('titulo',200);
                $t->text('mensaje');
                $t->json('referencia')->nullable();
                $t->boolean('leida')->default(false);
                $t->timestamps();
                $t->foreign('usuario_id')->references('id')->on('personal')->onDelete('cascade');
                $t->index(['usuario_id','leida'],'idx_notif_user');
            });
            echo "  notificaciones OK\n";
        }
    },
    'down' => function () { Capsule::schema()->dropIfExists('notificaciones'); },
];