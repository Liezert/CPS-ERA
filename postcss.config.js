import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

const CAPA_SCOPE = '.capa-scope';

/**
 * Scope seluruh selector capa-admin.css ke `.capa-scope` sehingga preflight &
 * utility Tailwind v3 milik app tidak bocor ke UI panel Filament.
 */
const scopeCapaAdmin = () => ({
    postcssPlugin: 'scope-capa-admin',
    Once(root) {
        const file = root.source?.input?.file ?? '';

        if (!file.replaceAll('\\', '/').endsWith('resources/css/capa-admin.css')) {
            return;
        }

        root.walkRules((rule) => {
            if (rule.parent?.type === 'atrule' && /keyframes$/i.test(rule.parent.name)) {
                return;
            }

            rule.selectors = rule.selectors.flatMap((selector) => {
                const trimmed = selector.trim();

                if (/^(html|body|:root|:host)$/.test(trimmed)) {
                    return [CAPA_SCOPE];
                }

                if (trimmed === '*') {
                    return [CAPA_SCOPE, `${CAPA_SCOPE} *`];
                }

                return [`${CAPA_SCOPE} ${trimmed}`];
            });
        });
    },
});

export default {
    plugins: [
        tailwindcss,
        autoprefixer,
        scopeCapaAdmin(),
    ],
};
