function getProfileInitial(): string {
    const heading = document.querySelector<HTMLElement>('.account-body h3');
    const name = heading?.textContent?.trim() ?? '';
    return name.charAt(0).toUpperCase() || 'U';
}

function createFallbackAvatar(image: HTMLImageElement): void {
    const container = image.closest<HTMLElement>('.account-avatar');
    if (!container || container.querySelector('.account-avatar-fallback')) return;

    image.style.display = 'none';

    const fallback = document.createElement('span');
    fallback.className = 'account-avatar-fallback';
    fallback.textContent = getProfileInitial();
    container.appendChild(fallback);
}

export function initAvatarFallback(): void {
    if (typeof document === 'undefined') return;

    document.addEventListener('error', (event) => {
        const target = event.target;
        if (target instanceof HTMLImageElement && target.matches('.account-avatar img')) {
            createFallbackAvatar(target);
        }
    }, true);
}
