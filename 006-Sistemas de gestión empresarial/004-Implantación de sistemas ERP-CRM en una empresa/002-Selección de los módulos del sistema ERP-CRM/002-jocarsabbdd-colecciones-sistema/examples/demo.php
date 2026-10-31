<?php
require __DIR__.'/../php/JocarsaORM.php';
$orm=new JocarsaORM('demo_orm','config.json',__DIR__.'/../libjocarsaorm.so');
$id=$orm->guardar('clientes',['nombre'=>'Ana','edad'=>28]);
print_r($orm->obtener('clientes',$id));
print_r($orm->buscar('clientes','nombre','Ana'));
$orm->actualizar('clientes',$id,['edad'=>29]);
print_r($orm->todos('clientes'));
$orm->eliminar('clientes',$id);
