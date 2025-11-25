const normalizeBase = (base) => {
  if (!base) {
    return '';
  }
  return base.endsWith('/') ? base.slice(0, -1) : base;
};

const resolveBaseUrl = () => {
  if (window?.ASSET_URL) {
    return window.ASSET_URL;
  }

  const meta = document.querySelector('meta[name="app-url"]');
  if (meta?.content) {
    return meta.content;
  }

  return window?.location?.origin || '';
};

export default function asset(path = '') {
  if (!path) {
    return '';
  }

  if (/^https?:\/\//i.test(path)) {
    return path;
  }

  const base = normalizeBase(resolveBaseUrl());
  const normalizedPath = path.startsWith('/') ? path : `/${path}`;

  // encode URI component except slashes
  const segments = normalizedPath.split('/').map((segment, index) => {
    if (index === 0) {
      return segment;
    }
    return encodeURIComponent(segment);
  });

  return `${base}${segments.join('/')}`;
}


