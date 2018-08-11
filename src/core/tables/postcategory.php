<?php

return [
    'name'=> 'postcategory',
    'title'=> 'Categories',
    'tools'=>['add','csv'],
    'commands'=>['edit','clone'],
    'id'=>'id',
    'csv'=> ['id','title'],
    'permissions'=>[
        'create'=>['admin'],
        'update'=>['admin']
    ],
    'fields'=> [
        'id'=> ['edit'=>false,'create'=>false],
        'title'=> ['title'=>'Name']
    ]
];
