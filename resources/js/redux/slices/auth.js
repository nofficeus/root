import {createAsyncThunk, createSlice} from "@reduxjs/toolkit";
import {route, thunkRequest} from "services/Http";
import context from "context";
import {assign} from "lodash";

export const authState = {
    user: null,
    credential: "email",
    userSetup: false,
    verification: {
        error: null,
        loading: false,
        basic: [],
        advanced: [],
        status: "unverified"
    }
};

export const initAuthState = () => {
    return assign({}, authState, context.auth);
};

export const fetchVerification = createAsyncThunk(
    "auth/fetchVerification",
    (arg, api) => {
        return thunkRequest(api).get(route("user.verification.get"));
    }
);

export const fetchUser = createAsyncThunk("auth/fetchUser", (arg, api) => {
    return thunkRequest(api).get(route("user.data"));
});

const auth = createSlice({
    name: "auth",
    initialState: authState,
    reducers: {
        setAuthUser: (state, action) => {
            state.user = action.payload;
        }
    },
    extraReducers: {
        [fetchUser.fulfilled]: (state, action) => {
            state.user = action.payload;
        },

        [fetchVerification.pending]: (state) => {
            state.verification = {
                ...state.verification,
                error: null,
                loading: true
            };
        },
        [fetchVerification.rejected]: (state, action) => {
            state.verification = {
                ...state.verification,
                error: action.error.message,
                loading: false
            };
        },
        [fetchVerification.fulfilled]: (state, action) => {
            state.verification = {
                ...state.verification,
                loading: false,
                error: null,
                basic: action.payload.basic,
                advanced: action.payload.advanced,
                status: action.payload.status
            };
        }
    }
});

export const {setAuthUser} = auth.actions;

export default auth.reducer;
