export function updateBlogSeo({ title, description, image, canonical, type = 'website', structuredData, publishedAt, modifiedAt }) {
    document.title = title;
    setMeta('name', 'description', description);
    setMeta('property', 'og:type', type);
    setMeta('property', 'og:title', title);
    setMeta('property', 'og:description', description);
    setMeta('property', 'og:url', canonical);
    setMeta('property', 'og:image', image);
    setMeta('name', 'twitter:card', image ? 'summary_large_image' : 'summary');
    setMeta('name', 'twitter:title', title);
    setMeta('name', 'twitter:description', description);
    setMeta('name', 'twitter:image', image);
    setMeta('property', 'article:published_time', publishedAt);
    setMeta('property', 'article:modified_time', modifiedAt);

    let canonicalLink = document.head.querySelector('link[rel="canonical"]');
    if (!canonicalLink) {
        canonicalLink = document.createElement('link');
        canonicalLink.rel = 'canonical';
        document.head.append(canonicalLink);
    }
    canonicalLink.href = canonical;

    let script = document.head.querySelector('#structured-data');
    if (!script) {
        script = document.createElement('script');
        script.id = 'structured-data';
        script.type = 'application/ld+json';
        document.head.append(script);
    }
    script.textContent = JSON.stringify(structuredData);
}

function setMeta(kind, key, value) {
    let element = document.head.querySelector(`meta[${kind}="${key}"]`);
    if (!element) {
        element = document.createElement('meta');
        element.setAttribute(kind, key);
        document.head.append(element);
    }

    if (value) {
        element.setAttribute('content', value);
    } else {
        element.remove();
    }
}
