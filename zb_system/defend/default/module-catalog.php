{$i = $maxLi}
{$j = 0}
{$s = ''}
{if $style==2}
    {foreach $catalogs as $catalog}
        {if $catalog->Level == 0}
            {$s = $s . '<li class="li-cate"><a href="' . $catalog->Url . '">' . $catalog->Name . '</a><!--' . $catalog->ID . 'begin--><!--' . $catalog->ID . 'end--></li>'}
        {/if}
    {/foreach}

    {for $i = 1; $i <= 3; $i++}
        {* 此处逻辑仍要继续修改 *}
        {foreach $catalogs as $catalog}
            {if $catalog->Level == $i}
                {$s = str_replace('<!--' . $catalog->ParentID . 'end-->', '<li class="li-subcate"><a href="' . $value->Url . '">' . $catalog->Name . '</a><!--' . $catalog->ID . 'begin--><!--' . $catalog->ID . 'end--></li><!--' . $catalog->ParentID . 'end-->', $s)}
            {/if}
        {/foreach}
    {/for}

    {foreach $catalogs as $catalog}
        {$s = str_replace('<!--' . $catalog->ID . 'begin--><!--' . $catalog->ID . 'end-->', '', $s)}
    {/foreach}
    {foreach $catalogs as $catalog}
        {$s = str_replace('<!--' . $catalog->ID . 'begin-->', '<ul class="ul-subcates">', $s)}
        {$s = str_replace('<!--' . $catalog->ID . 'end-->', '</ul>', $s)}
    {/foreach}
    {$s}
{elseif $style==1}
    {foreach $catalogs as $catalog}
        <li>{$catalog->Symbol}<a href="{$catalog.Url}">{$catalog.Name}</a></li>
        {$j =$j + 1}
        {if $i != 0 && $j >= $i}
            {php}break;{/php}
        {/if}
    {/foreach}
{else}
    {foreach $catalogs as $catalog}
        <li><a href="{$catalog.Url}">{$catalog.Name}</a></li>
        {$j =$j + 1}
        {if $i != 0 && $j >= $i}
            {php}break;{/php}
        {/if}
    {/foreach}
{/if}