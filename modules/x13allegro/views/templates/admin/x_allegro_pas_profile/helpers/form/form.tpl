{extends file="helpers/form/form.tpl"}

{block name="after"}
    <script>
        var XAllegro = new X13Allegro();
        XAllegro.pasForm();
    </script>
{/block}
