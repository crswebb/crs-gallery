(function (blocks, element, components, api) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var SelectControl = components.SelectControl;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;

    function getGalleryById(galleries, id) {
        console.log(galleries);
        return galleries.find((gallery) => gallery.id === id);
    }

    registerBlockType('crs/gallery', {
        name: 'crs/gallery',
        title: 'CRS Gallery',
        icon: 'format-gallery',
        category: 'common',
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
                    path: '/wp/v1/crs_gallery',
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

            useEffect(() => {
                const { galleries } = props.attributes;
                if (galleries.length > 0) {
                    const gallery = getGalleryById(galleries, galleryId);
                    if (gallery) {
                        setTitle(gallery.title);
                        setImages(gallery.images);
                    }
                }
            }, [galleryId, props.attributes.galleries]);

            if (isLoading) {
                return el(
                    'div',
                    { className: 'loading-message' },
                    'Loading galleries...'
                );
            }

            var options = props.attributes.galleries.map(function (gallery) {
                return { value: gallery.id, label: gallery.title };
            });

            options.unshift({ value: 0, label: 'No gallery selected' });

            function onGalleryIdChange(value) {
                props.setAttributes({ galleryId: value });
            }

            return el(
                'div',
                { className: 'gallery-block' },
                el(SelectControl, {
                    label: 'Gallery',
                    value: galleryId,
                    options: options,
                    onChange: onGalleryIdChange,
                }),
                el(
                    'p',
                    null,
                    'Selected Gallery ID: ',
                    galleryId !== 0 ? galleryId : 'None'
                )
            );
        },
        save: function (props) {
            const { galleryId, galleries } = props.attributes;
            const gallery = getGalleryById(galleries, galleryId);

            if (!gallery) {
                return null;
            }

            const { title, images } = gallery;

            return el(
                'div',
                { className: 'gallery' },
                el('h2', null, title),
                el(
                    'div',
                    { className: 'image-grid' },
                    images.map((image) =>
                        el('img', { key: image.id, src: image.url, alt: image.title })
                    )
                )
            );
        },
        deprecated: [
            {
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