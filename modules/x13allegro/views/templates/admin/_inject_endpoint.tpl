{* Place this near your page root element *}
{assign var=adminToken value=Tools::getAdminTokenLite('AdminXAllegroCategorySuggest')}
<script>$('#content').attr('data-x13-suggest-endpoint', 'index.php?controller=AdminXAllegroCategorySuggest&token={$adminToken|escape:'htmlall':'UTF-8'}');</script>
