
{{--
  Template Name: Home Template
--}}

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