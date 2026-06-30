let activeMenu = null;

const menuItems = {
    word: ['translate', 'context-explain', 'save', 'listen'],
    phrase: ['translate', 'context-explain', 'grammar-explain', 'simplify', 'listen', 'shadowing'],
    sentence: ['translate', 'context-explain', 'grammar-explain', 'simplify', 'listen', 'shadowing'],
    paragraph: ['translate', 'simplify', 'listen'],
};

const itemLabels = {
    translate: { label: 'Translate', icon: '⇄' },
    'context-explain': { label: 'Explain in context', icon: '⊡' },
    'grammar-explain': { label: 'Explain grammar', icon: '◈' },
    simplify: { label: 'Simplify', icon: '▽' },
    listen: { label: 'Listen', icon: '♢' },
    shadowing: { label: 'Shadowing', icon: '◉' },
    save: { label: 'Save', icon: '＋' },
};

const close = () => {
    if (activeMenu) {
        activeMenu.remove();
        activeMenu = null;
    }
};

const buildMenu = (items) => {
    const menu = document.createElement('div');
    menu.className = 'contextual-menu';
    menu.setAttribute('role', 'menu');
    items.forEach((key) => {
        const cfg = itemLabels[key];
        if (!cfg) return;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'contextual-menu-item';
        btn.setAttribute('role', 'menuitem');
        btn.dataset.action = key;
        btn.innerHTML = `<span>${cfg.icon}</span> ${cfg.label}`;
        menu.appendChild(btn);
    });
    return menu;
};

const position = (menu, x, y) => {
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const menuRect = menu.getBoundingClientRect();
    let left = x;
    let top = y;
    if (left + menuRect.width + 12 > vw) {
        left = vw - menuRect.width - 12;
    }
    if (top + menuRect.height + 12 > vh) {
        top = vh - menuRect.height - 12;
    }
    menu.style.left = `${Math.max(12, left)}px`;
    menu.style.top = `${Math.max(12, top)}px`;
};

export const showContextualMenu = (x, y, selectionType, onAction) => {
    close();
    const items = menuItems[selectionType] || menuItems.word;
    const menu = buildMenu(items);
    menu.addEventListener('click', (event) => {
        const btn = event.target.closest('.contextual-menu-item');
        if (!btn) return;
        event.stopPropagation();
        const action = btn.dataset.action;
        close();
        if (onAction) onAction(action);
    });
    document.body.appendChild(menu);
    position(menu, x, y);
    activeMenu = menu;
    const closeHandler = (event) => {
        if (event.type === 'keydown' && event.key !== 'Escape') return;
        if (activeMenu && !activeMenu.contains(event.target)) {
            close();
            document.removeEventListener('pointerdown', closeHandler);
            document.removeEventListener('keydown', closeHandler);
        }
    };
    document.addEventListener('pointerdown', closeHandler);
    document.addEventListener('keydown', closeHandler);
    return menu;
};

export { close as closeContextualMenu };
