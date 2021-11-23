import React, {useEffect} from "react";
import Router from "./router";
import context, {AppContext} from "context";
import {isEmpty} from "lodash";
import {notify} from "./utils";
import {useDispatch} from "react-redux";
import {
    fetchCountries,
    fetchOperatingCountries,
    fetchSupportedCurrencies,
    fetchWallets
} from "redux/slices/global";
import {useAuth} from "models/Auth";
import {fetchWalletAccounts} from "redux/slices/wallet";
import {useLocation} from "react-router-dom";
import {fetchPaymentAccount} from "redux/slices/payment";
import {useInstaller} from "hooks/settings";

const App = () => {
    const dispatch = useDispatch();
    const installer = useInstaller();
    const auth = useAuth();

    useEffect(() => {
        if (!installer && auth.check()) {
            dispatch(fetchWalletAccounts());
            dispatch(fetchPaymentAccount());
        }
    }, [dispatch, auth, installer]);

    useEffect(() => {
        const data = context.notification;
        if (!isEmpty(data) && data.message) {
            const type = data.type || "info";
            notify[type](data.message);
        }
    }, []);

    useEffect(() => {
        if (!installer) {
            dispatch(fetchCountries());
            dispatch(fetchSupportedCurrencies());
            dispatch(fetchOperatingCountries());
            dispatch(fetchWallets());
        }
    }, [dispatch, installer]);

    return (
        <AppContext.Provider value={context}>
            <ScrollToTop />
            <Router />
        </AppContext.Provider>
    );
};

const ScrollToTop = () => {
    const {pathname} = useLocation();

    useEffect(() => {
        window.scrollTo(0, 0);
    }, [pathname]);

    return null;
};

export default App;
