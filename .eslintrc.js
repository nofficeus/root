const path = require("path");

module.exports = {
    "env": {
        "browser": true,
        "es6": true,
        "commonjs": true,
        "node": true,
    },
    "extends": [
        "eslint:recommended",
        "plugin:react-hooks/recommended",
        "plugin:react/recommended",
        "prettier"
    ],
    "plugins": [
        "react",
        "react-hooks",
        "formatjs",
        "prettier"
    ],
    "parser": "@babel/eslint-parser",
    "parserOptions": {
        "babelOptions": {
            "configFile": path.resolve(__dirname, "babel.config.js")
        },
    },
    "rules": {
        "no-array-constructor": "error",
        "no-lonely-if": "warn",
        "no-multi-assign": "warn",
        "no-plusplus": ["warn", {"allowForLoopAfterthoughts": true}],
        "no-unneeded-ternary": "warn",
        "curly": "warn",
        "no-duplicate-imports": "error",
        "no-useless-computed-key": "warn",
        "no-useless-constructor": "warn",
        "no-useless-rename": "warn",
        "no-var": "error",
        "object-shorthand": "warn",
        "prefer-rest-params": "warn",
        "eqeqeq": "error",
        "yoda": "error",
        "no-eval": "error",
        "no-implied-eval": "error",
        "no-constructor-return": "error",
        "no-caller": "error",
        // "arrow-spacing": "warn",
        // "wrap-regex": "warn",
        "no-invalid-this": "error",
        "no-multi-str": "error",
        "guard-for-in": "warn",
        "no-alert": "warn",
        "no-iterator": "warn",
        "no-unused-vars": ["warn", {"ignoreRestSiblings": true, "argsIgnorePattern": "^_", "args": "none"}],
        "no-implicit-coercion": "warn",
        "no-new-wrappers": "warn",
        "no-return-assign": "warn",
        "no-self-compare": "warn",
        "no-unused-expressions": "warn",
        "no-useless-call": "warn",
        "no-useless-concat": "warn",
        "no-useless-return": "warn",
        "require-await": "warn",
        "no-prototype-builtins": "off",
        // "array-bracket-spacing": "warn",
        // "brace-style": "warn",
        // "func-call-spacing": "warn",
        "prefer-arrow-callback": "warn",
        // "rest-spread-spacing": "warn",
        // "switch-colon-spacing": "warn",
        // "semi-style": "warn",
        "spaced-comment": "warn",
        // "space-before-blocks": "warn",
        // "template-tag-spacing": "warn",
        "prefer-const": "warn",
        // "object-curly-spacing": "warn",
        // "comma-dangle": ["warn", "never"],
        // "arrow-parens": ["warn", "as-needed"],
        // "comma-style": "warn",
        // "key-spacing": ["warn", {"align": {"beforeColon": true, "afterColon": true, "on": "colon"}}],
        // "object-curly-newline": ["warn", {
        //     "ObjectExpression" : {"multiline": true, "minProperties": 2},
        //     "ObjectPattern" : {"multiline": true, "minProperties": 3},
        //     "ImportDeclaration" : {"minProperties": 3},
        //     "ExportDeclaration" : {"minProperties": 3},
        // }],
        // "computed-property-spacing": "warn",
        // "indent": ["warn", 4],
        // "quotes": ["warn", "double"],
        // "block-spacing": ["error", "never"],
        // Plugins
        "formatjs/no-offset": "error",
        "react/prop-types": "off",
        "react/display-name": "off",
        // "react/jsx-closing-bracket-location": ["warn", {selfClosing: 'after-props', nonEmpty: 'after-props'}],
        // "react/jsx-closing-tag-location": "warn",
        // "react/jsx-curly-newline": "warn",
        // "react/jsx-curly-spacing": "warn",
        // "react/jsx-equals-spacing": "warn",
        // "react/jsx-first-prop-new-line": "warn",
        // "react/jsx-wrap-multilines": ["warn", {
        //     declaration: "parens-new-line",
        //     assignment: "parens-new-line",
        //     return: "parens-new-line",
        //     arrow: "parens-new-line",
        //     condition: "parens-new-line",
        //     logical: "parens-new-line",
        //     prop: "parens-new-line"
        // }],
        "react/jsx-no-useless-fragment": "warn",
        // "react/jsx-props-no-multi-spaces": "warn",
        // "react/jsx-tag-spacing": "warn",
        "react/jsx-fragments": ["warn", "element"],
        "react-hooks/rules-of-hooks": "error",
        "react-hooks/exhaustive-deps": "error",
        "prettier/prettier": "error"
    },
    "globals": {
        "Mix": "readonly",
        "context": "readonly"
    },
    "settings": {
        "react": {
            "version": 'detect',
        },
    },
}
