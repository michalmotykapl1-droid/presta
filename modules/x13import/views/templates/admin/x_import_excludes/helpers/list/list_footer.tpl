{extends file="helpers/list/list_footer.tpl"}

{block name="after"}
    <style>
        #form-ximport_product table thead > tr > th.word-break > span {
            word-wrap: normal !important;
            white-space: normal !important;
        }

        #form-ximport_product .panel-footer {
            display: none !important;
        }
    </style>

    {literal}
    <script>
        var X13Import = new $.XImport();
        X13Import.excludeList();
    </script>
    {/literal}
{/block}
