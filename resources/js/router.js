import React, {useMemo} from "react";
import {lazy, routePath, router} from "./utils";
import {Switch, useLocation} from "react-router-dom";
import Redirect from "components/Redirect";
import {useSelector} from "react-redux";
import {get} from "lodash";
import ProtectedRoute from "./components/ProtectedRoute";
import basicMiddleware, {auth, guest, can} from "utils/middleware";
import {useInstaller} from "hooks/settings";

const Auth = lazy(() =>
    import(/* webpackChunkName: 'auth' */ "./layouts/Auth")
);
const Landing = lazy(() =>
    import(/* webpackChunkName: 'landing' */ "./layouts/Landing")
);
const Main = lazy(() =>
    import(/* webpackChunkName: 'main' */ "./layouts/Main")
);
const Admin = lazy(() =>
    import(/* webpackChunkName: 'admin' */ "./layouts/Admin")
);
const Installer = lazy(() =>
    import(/* webpackChunkName: 'installer' */ "./routes/installer")
);

const Router = () => {
    const installer = useInstaller();
    const location = useLocation();

    const showLanding = useSelector((state) => {
        return get(state, "landing.enable", true);
    });

    const landingMiddleware = useMemo(() => {
        const fallback = <Redirect to={router.generatePath("home")} />;
        return basicMiddleware(() => showLanding, fallback);
    }, [showLanding]);

    if (installer) {
        return <Installer />;
    }

    return (
        <Switch>
            <ProtectedRoute
                middleware={landingMiddleware}
                exact
                path={routePath()}>
                <Redirect to={router.generatePath("landing")} />
            </ProtectedRoute>

            <ProtectedRoute
                middleware={landingMiddleware}
                path={router.getPath("landing")}>
                <Landing />
            </ProtectedRoute>

            <ProtectedRoute
                middleware={[auth(location), can("access_control_panel")]}
                path={router.getPath("admin")}>
                <Admin />
            </ProtectedRoute>

            <ProtectedRoute
                middleware={guest(router.generatePath("home"))}
                path={router.getPath("auth")}>
                <Auth />
            </ProtectedRoute>

            <ProtectedRoute middleware={auth(location)} path={routePath()}>
                <Main />
            </ProtectedRoute>
        </Switch>
    );
};

export default Router;
