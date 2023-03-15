<div class="perfect-productbanner">
    
    {assign var=set value=0}

    {foreach from=$myURL item=element name=elementList}
        {if $element['cat_id'] == $product->id_category_default}
            <img src="{$element['urlimage']}" style='width:{$element['width']}; height:{$element['height']}' alt="{$element}" title="{$element}">
            {$set = 1}
        {/if}
    {/foreach}

    {if $set == 0 }
        {foreach from=$myURL item=element name=elementList}
            {if $element['cat_id'] == $category->id_parent}
                <img src="{$element['urlimage']}" style='width:{$element['width']}; height:{$element['height']}' alt="{$element}" title="{$element}">
            {/if}
        {/foreach}
    {/if}

</div>