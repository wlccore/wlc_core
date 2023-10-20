<!DOCTYPE html>
<html lang="en" data-html-lang>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Location restriction</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="msapplication-config" content="/static/images/favicons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">

    <style>
        :root {
            --base-color: white;
            --text-color: #ADADAD;
            --border-color: #575757;
            --icon-color: #575757;
            --background-color: #212121;
            --background-lines-color: #2c2c2c;
        }

        * {
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(
                    45deg,
                    var(--background-lines-color) 0,
                    var(--background-lines-color) 1%,
                    transparent 1%,
                    transparent 49%,
                    var(--background-lines-color) 49%,
                    var(--background-lines-color) 51%,
                    transparent 51%,
                    rgba(50, 50, 50, 0) 99%,
                    var(--background-lines-color) 99%,
                    var(--background-lines-color) 100%),
            var(--background-color);
            background-origin: padding-box;
            background-clip: border-box;
            background-size: 8px 8px;
            background-position: 50% 50%;
            text-align: center;
            font-family: Montserrat, sans-serif;
            color: var(--text-color);
        }

        .container {
            max-width: 73.125rem;
            margin: auto;
        }

        .column {
            float: left;
            width: 100%;
        }

        .column-6 {
            padding: 1rem;
        }

        .column--main {
            margin-bottom: 1.5625rem;
            padding-top: 2.5rem
        }

        .column--tech_info {
            padding-top: 1.5625rem;
        }

        h1 {
            color: var(--base-color);
            font-size: 1.125rem;
            margin: 0 0 0.625rem 0;
        }

        p {
            font-size: 0.875rem;
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 0;
        }

        a {
            color: var(--text-color);
        }

        p strong {
            font-size: 1.125rem;
            font-weight: 800;
            color: var(--base-color);
        }

        .logo {
            max-height: 3.125rem;
        }

        svg path {
            fill: var(--icon-color);
            fill-rule: evenodd;
        }

        .icon-wrapper {
            margin-bottom: 1rem;
        }

        @media screen and (min-width: 768px) {
            .container {
                display: -webkit-box;
                display: -ms-flexbox;
                display: flex;
                -ms-flex-wrap: wrap;
                flex-wrap: wrap;
            }

            .column-6 {
                width: 50%;
                padding: 1rem;
                display: flex;
                align-items: center;
                flex-direction: column;
                justify-content: center;
            }

            .column--not-restricted {
                border: 2px solid var(--border-color);
                border-radius: 2rem;
            }

            .logo {
                max-height: 5rem;
            }

            h1 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }

            p {
                font-size: 1.125rem;
            }

            .column--main {
                margin-bottom: 2.875rem;
            }
        }

    </style>

</head>

<body>

<div class="container">

    <div class="column column--main">
        <p>{{ useCustomLogo ? useCustomLogo|raw : '<img src="/static/images/logo.svg" class="logo" />' }}</p>
        <p></p>

        <h1>Location restriction</h1>

        <p style="max-width:28.125rem;margin:auto;">
            Due to legal reasons we can't accept players residing in the country you are currently
            in.
        </p>

    </div>

    <div class="column column-6 column--not-restricted">
        <div class="icon-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60"><path d="M50.03 17.39l-.083-.38c-.89.295-1.777.804-2.675.826-1.002.027-2.033-.333-3.018-.63-.967-.294-1.894-.72-3.074-1.18l-1.383.613c1.15.37 2.436.59 3.527 1.178 2.395 1.29 4.536.55 6.707-.428M22.97 4.044C15.17 6.424 9.48 11.078 5.882 18.24c-.17.34-.216.764-.232 1.154-.104 2.327.57 4.446 1.49 6.578.816 1.892 2.186 3.154 4.456 3.248l-.673-2.483c-2.914.416-4.23-.996-3.588-3.766.176-.758.494-1.545.953-2.168 1.264-1.718 3.118-1.813 4.646-.32.275.27.453.638.745 1.06 1.913-3.93 4.544-6.9 7.35-9.715.673-.677 1.62-1.073 2.42-1.63.184-.13.317-.373.4-.59.76-1.97-.195-3.66-.88-5.563m-3.412 50.55c-.348-.828-.594-1.46-.876-2.078-.942-2.068-1.66-4.15-1.224-6.504.235-1.27-.36-2.305-1.376-3.148a22.68 22.68 0 0 1-2.668-2.59c-1.338-1.545-2.056-3.326-1.715-5.437.15-.94.202-1.894.297-2.82-1.334-.55-2.598-1.05-3.846-1.588-1.444-.62-2.388-1.672-2.87-3.202-.328-1.046-.847-2.033-1.356-3.222C.747 36.28 7.774 50.002 19.558 54.594m1.59-1.833c.702-.806 1.218-1.795 2.033-2.255 1.905-1.078 3.046-2.672 3.85-4.61 1.308-3.15 2.623-6.298 3.983-9.56-3.966.213-6.912-2.028-9.676-4.656-.4-.38-.907-.666-1.403-.92-1.084-.556-2.148-.822-3.224.126-.435.384-1.108.49-1.582.84-.404.3-.845.722-.986 1.177-1.05 3.37.172 5.993 2.91 7.958 2.097 1.508 2.897 3.507 2.518 5.966-.36 2.33.936 4.1 1.578 5.935m29.32-12.936l.257.09c.275-.86.665-1.702.805-2.584.45-2.85 1.19-5.553 3.025-7.885.61-.773 1.08-1.72 1.367-2.663.27-.884.48-1.93.264-2.795-2.246-9-7.798-15.23-16.252-18.888-1.73-.75-3.607-1.167-5.417-1.736l-.366.406c.247.556.344 1.365.77 1.62 1.07.634 2.186 1.272 3.606.753.845-.308 1.885-.348 2.542.54.663.894.345 1.85-.075 2.737a15.782 15.782 0 0 1-1.178 2.097c-.783 1.156-1.628 2.267-2.447 3.397l.148.32c.974-.5 2.13-.813 2.882-1.543 1.202-1.165 2.206-1.115 2.95.39.203.416.715.73 1.153.967.415.223.943.226 1.372.43.817.39 1.54.297 2.258-.214.226-.16.488-.282.748-.386 1.32-.53 2.765.274 3.383 1.875.478 1.233-.08 2.295-1.497 2.794-.643.228-1.334.34-1.95.62-1.893.867-3.753.65-5.61-.08-.67-.264-1.328-.82-1.985-.81-1.172.013-2.444.138-3.478.63-.783.372-1.288 1.365-1.86 2.126-.22.293-.41.715-.387 1.063.077 1.24.176 2.488.404 3.708.39 2.088 1.694 2.876 3.814 2.67 1.504-.147 3.034-.003 4.548-.058 1.44-.052 2.183.774 2.734 1.942 1.04 2.208 2.13 4.394 3.13 6.62.25.555.237 1.23.344 1.847m6.4-9.695c-2.02 2.44-2.466 3.44-2.97 6.366-.142.83-.273 1.67-.512 2.475-.555 1.864-1.617 3.427-3.207 4.536-.444.31-1.284.426-1.755.215-.4-.178-.715-.95-.733-1.474-.03-.822.24-1.65.345-2.482.048-.378.133-.824-.012-1.142a190.21 190.21 0 0 0-2.948-6.173c-.172-.346-.55-.794-.865-.82-1.31-.107-2.654-.242-3.94-.056-3.086.444-5.612-.8-6.257-3.495-.428-1.785-.603-3.645-.717-5.48-.037-.6.358-1.322.758-1.83.946-1.204 2.012-2.313 3.08-3.517-.352-.082-.59-.118-.818-.192-1.778-.573-2.15-1.842-.96-3.322.35-.438.79-.81 1.11-1.268.92-1.312 1.795-2.652 2.688-3.982l-.224-.393c-1.835 1.095-3.282-.013-4.845-.58-2.054-.747-2.102-2.493-2.146-4.455l-6.36.365c.216 2.255.403 4.254.608 6.25.02.19.146.367.223.55.616 1.505.12 2.198-1.503 2.196-.57 0-1.357-.034-1.676.3-2.924 3.058-6.19 5.888-7.456 10.17-.067.224-.194.44-.328.637-.947 1.384-2.218 1.333-3-.106-.153-.28-.286-.604-.517-.808-.4-.352-.98-.96-1.28-.857-.468.16-.88.773-1.098 1.286-.196.463-.1 1.05-.142 1.74.487-.16.75-.223.995-.327 1.716-.722 2.516-.186 2.734 1.682.14 1.214.475 2.405.758 3.774.71-.373 1.366-.602 1.88-1.01 1.123-.886 2.317-.97 3.64-.64 1.786.445 3.15 1.535 4.46 2.747 1.517 1.408 3.15 2.64 5.258 3.01.83.145 1.682.17 2.503.347 1.697.365 2.31 2.11 1.39 3.582-.688 1.107-1.282 2.284-1.812 3.478-.86 1.943-1.577 3.953-2.462 5.885-.752 1.642-1.55 3.38-3.267 4.204-1.644.788-1.966 1.953-1.757 3.59.1.77.297 1.076 1.088 1.245 10.78 2.306 22.41-2.86 28.206-12.57 2.458-4.12 3.722-8.575 3.84-13.652M29.904 59C13.776 58.985.98 45.97 1 29.604 1.02 13.864 14.123.978 30.084 1 46.077 1.022 59.026 14.022 59 30.026 58.973 46.096 45.998 59.014 29.903 59"/></svg>
        </div>

        <p style="margin: 0;">
            <strong>Not in a restricted region?</strong>
        </p>
        <p style="font-size:0.875rem;margin: 0 auto;max-width:20rem">
            If you’re using a proxy service or VPN to access casino, try turning it off and reload the
            page.
        </p>

    </div>

    <div class="column column-6 column--contact">

        <div class="icon-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="72" height="60" viewBox="0 0 72 60"><path d="M63.09 55c2.59 0 4.69-2.117 4.69-4.73V15.784c0-2.615-2.1-4.73-4.69-4.73H8.91c-2.59 0-4.69 2.115-4.69 4.73V50.27c0 2.614 2.1 4.73 4.69 4.73zm0 3H8.91c-4.25 0-7.69-3.464-7.69-7.73V15.784c0-4.267 3.44-7.73 7.69-7.73h54.18c4.25 0 7.69 3.463 7.69 7.73V50.27c0 4.267-3.44 7.73-7.69 7.73z"/><path d="M6.918 15.53l23.577 21.53a8.13 8.13 0 0 0 10.997-.02l23.38-21.51a1.5 1.5 0 0 0-2.032-2.207L39.462 34.83a5.13 5.13 0 0 1-6.944.015L8.94 13.315A1.5 1.5 0 1 0 6.92 15.53z"/><path d="M27.292 31.92L6.918 50.52a1.5 1.5 0 1 0 2.022 2.215l20.375-18.6a1.5 1.5 0 1 0-2.023-2.216zm37.575 18.603l-20.15-18.4a1.5 1.5 0 0 0-2.024 2.216l20.152 18.398a1.5 1.5 0 1 0 2.023-2.215z"/></svg>
        </div>

        <p style="margin: 0;">
            <strong>Do you need to contact us?</strong>
        </p>
        <p style="font-size:0.875rem;margin: 0 auto;max-width:20rem">
            Should you have any questions or feel that this block is affecting you due to a technical error,
            please
            don't hesitate to contact us and we'll work it out together.
        </p>
        <p><a href="mailto:{{supportEmail}}">{{supportEmail}}</a></p>
    </div>
    <div class="column column--tech_info">
        {{ userIP }} ({{ userCountry }})
    </div>
</div>


</body>

</html>
