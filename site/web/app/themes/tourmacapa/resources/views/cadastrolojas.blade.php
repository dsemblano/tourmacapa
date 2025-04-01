
{{--
  Template Name: Cadastro lojas
--}}

@extends('layouts.app')

@section('content')

<section id="cadastro_lojas" class="bg-slate-100 lg:arrow-border py-6">
    <div class="container">
        <?php 
        acf_form(array(
        'post_id'       => 'new_post', // Save as new post
        'post_title'    => false, // Hide title field
        'post_content'  => false, // Hide content field
        'fields'        => array('nome_estabelecimento', 'email', 'cnpj', 'telefone', ), // Your ACF field keys
        'submit_value'  => 'Enviar cadastro',
        'new_post'      => array(
            'post_type'   => 'cadastro-loja', // Custom post type
            'post_status' => 'publish',
        ),
        ));
        ?>
    </div>
</section>

@endsection