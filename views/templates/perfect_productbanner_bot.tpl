<div class="perfect-productbanner">
    
    {assign var=set value=0}

    {foreach from=$myURL item=element name=elementList}
        {if $element['cat_id'] == $product->id_category_default}
            {if $element['position'] == 2}
                <img src="{$element['urlimage']}" style='margin: 15px; width:{$element['width']}; height:{$element['height']}' alt="{$element}" title="{$element}">
                {$set = 1}
            {/if}
        {/if}
    {/foreach}

    {if $set == 0 }
        {foreach from=$myURL item=element name=elementList}
            {if $element['cat_id'] == $category->id_parent}
                {if $element['position'] == 2}
                    <img src="{$element['urlimage']}" style='width:{$element['width']}; height:{$element['height']}' alt="{$element}" title="{$element}">
                {/if}
            {/if}
        {/foreach}
    {/if}

</div>