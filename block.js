(function (blocks, element, components, api) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var SelectControl = components.SelectControl;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var __ = wp.i18n.__;

    registerBlockType('crs/gallery', {
        name: 'crs/gallery',
        title: __('CRS Gallery', 'crs-gallery'),
        icon: 'format-gallery',
        category: 'media',
        attributes: {
            galleryId: {
                type: 'number',
                default: 0,
            },
            galleries: {
                type: 'array',
                default: [],
            },
        },
        edit: function (props) {
            var galleryId = props.attributes.galleryId;
            var [isLoading, setIsLoading] = useState(true);

            useEffect(function () {
                wp.apiFetch({
                    path: '/crs/v1/crs_gallery',
                })
                    .then(function (response) {
                        props.setAttributes({ galleries: response });
                        setIsLoading(false);
                    })
                    .catch(function (error) {
                        console.error(error);
                        setIsLoading(false);
                    });
            }, []);

            if (isLoading) {
                return el(
                    'div',
                    { className: 'loading-message' },
                    __('Loading galleries...', 'crs-gallery')
                );
            }

            var options = props.attributes.galleries.map(function (gallery) {
                return { value: gallery.id, label: gallery.title };
            });

            options.unshift({ value: 0, label: __('No gallery selected', 'crs-gallery') });

            function onGalleryIdChange(value) {
                props.setAttributes({ galleryId: parseInt(value, 10) || 0 });
            }

            return el(
                'div',
                { className: 'gallery-block' },
                el(SelectControl, {
                    label: __('Gallery', 'crs-gallery'),
                    value: galleryId,
                    options: options,
                    onChange: onGalleryIdChange,
                }),
                el(
                    'p',
                    null,
                    __('Selected Gallery ID: ', 'crs-gallery'),
                    galleryId !== 0 ? galleryId : __('None', 'crs-gallery')
                )
            );
        },
        // Dynamiskt block – utdata renderas server-side via render_callback
        // (crs_render_gallery_block), så save returnerar null.
        save: function () {
            return null;
        },
        deprecated: [
            {
                // Matches the previous static save (markup baked into post
                // content) so existing blocks migrate to this dynamic block
                // instead of failing Gutenberg validation.
                attributes: {
                    galleryId: {
                        type: 'number',
                        default: 0,
                    },
                    galleries: {
                        type: 'array',
                        default: [],
                    },
                },
                save: function (props) {
                    var galleryId = props.attributes.galleryId;
                    var galleries = props.attributes.galleries;
                    var gallery = galleries.find(function (g) {
                        return g.id === galleryId;
                    });

                    if (!gallery) {
                        return null;
                    }

                    return el(
                        'div',
                        { className: 'gallery' },
                        el('h2', null, gallery.title),
                        el(
                            'div',
                            { className: 'image-grid' },
                            gallery.images.map(function (image) {
                                return el('img', { key: image.id, src: image.url, alt: image.title });
                            })
                        )
                    );
                },
            },
            {
                // Oldest format: a shortcode wrapper.
                attributes: {
                    galleryId: {
                        type: 'string',
                        default: '0',
                    },
                    galleries: {
                        type: 'array',
                        default: [],
                    },
                },
                save: function (props) {
                    return el('div', { className: 'wp-block-crs-gallery' }, '[crs-gallery galleryId="' + props.attributes.galleryId + '"]');
                },
            },
        ],
    });
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.components,
    window.wp.api
);