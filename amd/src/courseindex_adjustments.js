define(['core/config'], function(cfg) {
    return {
        init: function() {
            console.log('HOLAAAAAA1');

            /**
             * Construye la URL del ícono de un módulo usando theme/image.php
             * (igual que get_icon_url en PHP).
             */
            const getIconUrl = function(modname) {
                return cfg.wwwroot +
                    '/theme/image.php/' +
                    cfg.theme + '/' +
                    modname + '/' +
                    cfg.themerev + '/' +
                    'monologo?filtericon=1';
            };

            /**
             * Inyecta íconos en las actividades (CMs).
             */
            const injectIcons = function(root) {
                root.querySelectorAll('.activityiconcontainer[data-cmid]').forEach(function(container) {
                    if (container.dataset.iconInjected) {
                        return;
                    }
                    console.log('HOLAAAAAA2');
                    container.dataset.iconInjected = '1';

                    const link = container.closest('a.courseindex-link');
                    if (!link) {
                        return;
                    }

                    const href = link.getAttribute('href') || '';
                    const match = href.match(/\/mod\/([^\/]+)\//);
                    if (!match) {
                        return;
                    }
                    const modname = match[1];
                    const iconurl = getIconUrl(modname);

                    const img = document.createElement('img');
                    img.src = iconurl;
                    img.alt = modname + ' icon';
                    img.className = 'icon activityicon';
                    container.appendChild(img);
                });
            };

            /**
             * Agrega el atributo title a los enlaces del índice (secciones y CMs).
             */
            const injectTitles = function(root) {
                root.querySelectorAll('a.courseindex-link').forEach(function(link) {
                    if (!link.hasAttribute('title')) {
                        const text = link.textContent.trim();
                        if (text) {
                            link.setAttribute('title', text);
                        }
                    }
                });
            };

            /**
             * Procesa un nodo nuevo (íconos + titles).
             */
            const processNode = function(node) {
                if (node.nodeType !== 1) {
                    return;
                }
                injectIcons(node);
                injectTitles(node);
            };

            // Corre una vez por si ya existen nodos.
            injectIcons(document);
            injectTitles(document);

            // Observa cambios en el índice (porque se renderiza vía JS).
            const target = document.querySelector('#courseindex');
            if (target) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(m) {
                        m.addedNodes.forEach(processNode);
                    });
                });
                observer.observe(target, {childList: true, subtree: true});
            }
        }
    };
});
