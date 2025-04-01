{{--
  Template Name: Cadastro lojas
--}}
@php acf_form_head(); @endphp

@extends('layouts.app')

@section('content')
    <section id="cadastro_lojas" class="bg-slate-100 lg:arrow-border py-6">
        <div class="container">
            <form id="loja-registration-form" class="acf-form">
            <?php
                wp_nonce_field('acf_nonce', 'acf_nonce'); 

                acf_form([
                    'post_id' => 'new_post', // Save as new post
                    'post_title' => true, // Required for WordPress (even if hidden)
                    'post_content' => false,
                    'fields' => ['field_67eb5b4d107f4', 'field_67eb5b78107f5', 'field_67eb5b9c107f6', 'field_67eb5bb6107f7', 'field_67eb73cdfff71'],
                    'submit_value'  => 'Enviar',
                    'new_post' => [
                        'post_type'   => 'cadastro-loja',
                        'post_status' => 'publish',
                    ],
                    'html_before_fields' => '
                    <style>
                        .acf-field--post-title { display: none; }
                    </style>
                    ',
                    'return'        => '#', // Prevent default redirect
                    'form'          => false,
                    
                ]);
            ?>
            <button type="submit" class="acf-button bg-primary hover:bg-gray-400 text-white text-xl mt-8 py-4 px-4 rounded-lg inline-flex items-center">Enviar</button>
            </form>
            <div id="form-response" style="display:none;"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
        $('#loja-registration-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            
            $form.find('button[type="submit"]').prop('disabled', true);
            
            $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: $form.serialize() + '&action=submit_loja_form',
            success: function(response) {
                if (response.success) {
                $('#form-response')
                    .html('<div class="success-message">✅ Cadastro realizado com sucesso!</div>')
                    .fadeIn();
                $form[0].reset();
                } else {
                $('#form-response')
                    .html('<div class="error-message">❌ ' + (response.data || 'Erro desconhecido') + '</div>')
                    .fadeIn();
                }
            },
            error: function(xhr) {
                $('#form-response')
                .html('<div class="error-message">❌ Erro de conexão</div>')
                .fadeIn();
            },
            complete: function() {
                $form.find('button[type="submit"]').prop('disabled', false);
            }
            });
        });
        });
        </script>
            
    </section>
@endsection
