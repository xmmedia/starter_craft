/**
 * The mobile menu is driven by invoker commands (command/commandfor) on the
 * buttons in _layout.twig, so it opens & closes without any JS in supporting
 * browsers. This adds a fallback for browsers without invoker support, and
 * closes the menu when it would otherwise be left covering the page.
 */

const supportsInvokerCommands = 'command' in HTMLButtonElement.prototype;

const runCommand = (event) => {
    const button = event.target.closest('button[commandfor][command]');
    if (!button) {
        return;
    }

    const target = document.getElementById(button.getAttribute('commandfor'));
    if (!target) {
        return;
    }

    switch (button.getAttribute('command')) {
        case 'show-modal':
            target.showModal();
            break;
        case 'close':
            target.close();
            break;
        case 'toggle-popover':
            target.togglePopover();
            break;
    }
};

export const initMenu = () => {
    const mobileMenu = document.getElementById('mobile-menu');
    if (!mobileMenu) {
        return;
    }

    if (!supportsInvokerCommands) {
        document.addEventListener('click', runCommand);
    }

    // anchor links don't unload the page, so the menu would stay open over it
    mobileMenu.addEventListener('click', (event) => {
        if (event.target.closest('a[href]')) {
            mobileMenu.close();
        }
    });

    // matches the md breakpoint, where the desktop nav is displayed
    const desktop = window.matchMedia('(width >= 48rem)');
    desktop.addEventListener('change', (event) => {
        if (event.matches) {
            mobileMenu.close();
        }
    });
};
