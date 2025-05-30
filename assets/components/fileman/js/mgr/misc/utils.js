FileMan.utils.renderBoolean = function (value) {
    return value
        ? String.format('<span class="green">{0}</span>', _('yes'))
        : String.format('<span class="red">{0}</span>', _('no'));
};

// Helper title render
FileMan.utils.renderName = function (value, props, row) {
    return value + ( row.data['description'] ? '<br/><small>' + row.data['description'] + '</small>' : '');
}

FileMan.utils.renderThumb = function(val, cell, row) {
    const source = this.source || MODx.config.default_media_source
    const extension = row.data.extension
    const imageExtensions = ["jpg", "png", "webp"];
    if(imageExtensions.includes(extension)) {
        const path = row.data.path
        return '<img src="' + MODx.config.connectors_url + 'system/phpthumb.php?h=50&w=150&far=0&src=' + path + '&source=' + source + '" />'
    }

    return '';
};

FileMan.utils.getMenu = function (actions, grid, selected) {
    const menu = [];
    let cls, icon, title, action;

    let has_delete = false;
    for (const i in actions) {
        if (!actions.hasOwnProperty(i)) {
            continue;
        }

        const a = actions[i];
        if (!a['menu']) {
            if (a == '-') {
                menu.push('-');
            }
            continue;
        }
        else if (menu.length > 0 && !has_delete && (/^remove/i.test(a['action']) || /^delete/i.test(a['action']))) {
            menu.push('-');
            has_delete = true;
        }

        if (selected.length > 1) {
            if (!a['multiple']) {
                continue;
            }
            else if (typeof(a['multiple']) == 'string') {
                a['title'] = a['multiple'];
            }
        }

        icon = a['icon'] ? a['icon'] : '';
        if (typeof(a['cls']) == 'object') {
            if (typeof(a['cls']['menu']) != 'undefined') {
                icon += ' ' + a['cls']['menu'];
            }
        }
        else {
            cls = a['cls'] ? a['cls'] : '';
        }
        title = a['title'] ? a['title'] : a['title'];
        action = a['action'] ? grid[a['action']] : '';

        menu.push({
            handler: action,
            text: String.format(
                '<span class="{0}"><i class="x-menu-item-icon {1}"></i>{2}</span>',
                cls, icon, title
            ),
            scope: grid
        });
    }

    return menu;
};

FileMan.utils.renderActions = function (value, props, row) {
    const res = [];
    let cls, icon, title, action, item;
    for (const i in row.data.actions) {
        if (!row.data.actions.hasOwnProperty(i)) {
            continue;
        }
        const a = row.data.actions[i];
        if (!a['button']) {
            continue;
        }

        icon = a['icon'] ? a['icon'] : '';
        if (typeof(a['cls']) == 'object') {
            if (typeof(a['cls']['button']) != 'undefined') {
                icon += ' ' + a['cls']['button'];
            }
        }
        else {
            cls = a['cls'] ? a['cls'] : '';
        }
        action = a['action'] ? a['action'] : '';
        title = a['title'] ? a['title'] : '';

        item = String.format(
            '<li class="{0}"><button class="fileman-btn fileman-btn-default {1}" action="{2}" title="{3}"></button></li>',
            cls, icon, action, title
        );

        res.push(item);
    }

    return String.format(
        '<ul class="fileman-row-actions">{0}</ul>',
        res.join('')
    );
};