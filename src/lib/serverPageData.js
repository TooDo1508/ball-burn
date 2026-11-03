export function getServerPageData(type) {
    const element = document.querySelector('#initial-blog-data');
    if (!element?.textContent) return null;

    try {
        const payload = JSON.parse(element.textContent);
        return payload?.type === type ? payload : null;
    } catch {
        return null;
    }
}
