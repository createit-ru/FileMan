{set $lastGroup = ''}
{foreach $files as $file}
    {if $file['group'] != $lastGroup}
        <h2>{$file['group']}</h2>
    {/if}
    <p>
        <a href="{$file['url']}" download>{$file['title'] ?: $file['name']}</a>
        {if $file['description']}<br /><small>{$file['description']}</small>{/if}
    </p>
    {set $lastGroup = $file['group']}
{/foreach}