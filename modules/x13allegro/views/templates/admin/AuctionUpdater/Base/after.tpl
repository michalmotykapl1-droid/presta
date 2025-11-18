<div class="form-group row">
    <div class="col-lg-12">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="allegro_skip_if_exists" type="checkbox" value="1" checked>
                {$data.title}
            </label>

            {if $data.description}
            <p class="help-block">
                {$data.description nofilter}
            </p>
            {/if}
        </div>
    </div>
</div>