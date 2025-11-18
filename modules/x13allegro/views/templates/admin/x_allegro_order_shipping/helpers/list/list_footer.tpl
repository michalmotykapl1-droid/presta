{extends file="helpers/list/list_footer.tpl"}

{block name="after"}
    <script>
        $(document).ready(function() {
            var XAllegro = new X13Allegro();
            XAllegro.orderShipping();
        });
    </script>
{/block}
