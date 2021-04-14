const {registerBlockType} = wp.blocks; //Blocks API
const {createElement} = wp.element; //React.createElement
const {__} = wp.i18n; //translation functions
const {InspectorControls} = wp.editor; //Block inspector wrapper
const {TextControl,SelectControl,ServerSideRender} = wp.components; //WordPress form inputs and server-side renderer

registerBlockType( 'carrier-matrix/installation', {
    title: __( 'Add Carrier Matrix' ), // Block title.
    category:  __( 'common' ), //category
    attributes:  {
        form : {
            default: null,
        },
        results: {
            default: null
        },
        button_text: {
            default: 'Search'
        }
    },
    //display the post title
    edit(props){
        const attributes =  props.attributes;
        const setAttributes =  props.setAttributes;

        function changeForm(form){
            setAttributes({form});
        }

        function changeResults(results){
            setAttributes({results});
        }

        function changeButtonText(button_text){
            setAttributes({button_text});
        }

        //Display block preview and UI
        return createElement('div', {}, [
            //Preview a block with a PHP render callback
            createElement( ServerSideRender, {
                block: 'carrier-matrix/installation',
                attributes: attributes
            } ),
            //Block inspector
            createElement( InspectorControls, {},
                [
                    createElement(TextControl, {
                        value: attributes.button_text,
                        label: __( 'Button Text' ),
                        onChange: changeButtonText,
                        type: 'text',
                    }),
                    createElement(SelectControl, {
                        value: attributes.form,
                        label: __( 'Form' ),
                        onChange: changeForm,
                        options: [
                            {value: 'product', label: 'Product'},
                            {value: 'abr', label: 'ABR'},
                            {value: 'contact', label: 'Contact'},
                            {value: 'report', label: 'Report'},
                        ]
                    }),
                    createElement(SelectControl, {
                        value: attributes.results,
                        label: __( 'Results' ),
                        onChange: changeResults,
                        options: [
                            {value: 'product', label: 'Product'},
                            {value: 'abr', label: 'ABR'},
                            {value: 'contact', label: 'Contact'},
                            {value: 'report', label: 'Report'},
                        ]
                    })
                ]
            )
        ] )
    },
    save(){
        return null;//save has to exist. This all we need
    }
});
