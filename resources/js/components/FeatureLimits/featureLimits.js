import React, {
    Fragment,
    useCallback,
    useEffect,
    useMemo,
    useState
} from "react";
import {
    Box,
    Card,
    CardContent,
    CardHeader,
    Chip,
    Grid,
    Stack,
    Typography
} from "@mui/material";
import {useAuth} from "models/Auth";
import {errorHandler, route, useRequest} from "services/Http";
import {defineMessages, FormattedMessage, useIntl} from "react-intl";
import FeatureLimit from "models/FeatureLimit";
import Spin from "../Spin";
import {isEmpty} from "lodash";
import Result from "../Result";
import {experimentalStyled as styled} from "@mui/material/styles";
import {Icon} from "@iconify/react";
import Scrollbar from "../Scrollbar";
import {formatNumber} from "utils/formatter";
import {calculatePercent} from "utils/helpers";
import CircularProgressWithLabel from "../CircularProgressWithLabel";

const messages = defineMessages({
    unverified: {defaultMessage: "Unverified"},
    basic: {defaultMessage: "Basic"},
    advanced: {defaultMessage: "Advance"},
    empty: {defaultMessage: "No Record!"}
});

const FeatureLimits = ({height}) => {
    const auth = useAuth();
    const [request, loading] = useRequest();
    const status = auth.get("verification.status");
    const [features, setFeatures] = useState([]);
    const intl = useIntl();

    const fetchFeatures = useCallback(() => {
        request
            .get(route("feature-limit.all"))
            .then((features) => setFeatures(features))
            .catch(errorHandler());
    }, [request]);

    useEffect(() => {
        fetchFeatures();
    }, [fetchFeatures]);

    const tags = useMemo(() => {
        return {
            unverified: (
                <Chip
                    size="small"
                    label={intl.formatMessage(messages.unverified)}
                    color="default"
                />
            ),
            basic: (
                <Chip
                    size="small"
                    label={intl.formatMessage(messages.basic)}
                    color="info"
                />
            ),
            advanced: (
                <Chip
                    size="small"
                    label={intl.formatMessage(messages.advanced)}
                    color="primary"
                />
            )
        };
    }, [intl]);

    const renderFeature = (data) => {
        const feature = FeatureLimit.use(data);

        const gridName = (
            <Grid item xs={8} sm={6} alignItems="center">
                <Stack direction="row" alignItems="center" spacing={2}>
                    <IconContainer>
                        <StyledIcon icon={feature.icon()} color="primary" />
                    </IconContainer>

                    <Typography variant="body2" noWrap>
                        {feature.title}
                    </Typography>
                </Stack>
            </Grid>
        );

        const percent = calculatePercent(feature.usage, feature.limit);

        const gridPercent = (
            <Grid
                item
                sx={{
                    alignItems: "center",
                    display: {xs: "none", sm: "flex"},
                    justifyContent: "center"
                }}
                sm={3}>
                <CircularProgressWithLabel value={percent} />
            </Grid>
        );

        const gridData = (
            <Grid
                item
                sx={{textAlign: "right", whiteSpace: "nowrap"}}
                xs={4}
                sm={3}>
                {!feature.limit ? (
                    <Typography variant="body2">
                        <FormattedMessage defaultMessage="Disabled" />
                    </Typography>
                ) : (
                    <Fragment>
                        <Typography
                            component="span"
                            sx={{mr: 0.5}}
                            variant="body2">
                            {formatNumber(feature.usage)}

                            <Typography
                                component="span"
                                sx={{color: "text.secondary", mx: 0.3}}
                                variant="caption">
                                <FormattedMessage defaultMessage="of" />
                            </Typography>

                            {formatNumber(feature.limit)}
                        </Typography>

                        <Typography
                            component="span"
                            sx={{color: "text.secondary"}}
                            variant="caption">
                            {feature.unit(auth.user)}
                        </Typography>
                    </Fragment>
                )}
            </Grid>
        );

        return (
            <Stack
                key={feature.name}
                justifyContent="center"
                sx={{minHeight: 40}}>
                <Grid container alignItems="center" spacing={2}>
                    {gridName}
                    {gridPercent}
                    {gridData}
                </Grid>
            </Stack>
        );
    };

    return (
        <Card
            sx={{
                display: "flex",
                flexDirection: "column",
                maxHeight: "100%"
            }}>
            <CardHeader
                title={<FormattedMessage defaultMessage="Account Limits" />}
                action={tags[status]}
            />

            <CardContent>
                <Scrollbar
                    sx={{
                        ...(height && {
                            maxHeight: `calc(${height}px - 100px)`
                        })
                    }}>
                    <Spin spinning={loading}>
                        {!isEmpty(features) ? (
                            <Stack spacing={2}>
                                {features.map(renderFeature)}
                            </Stack>
                        ) : (
                            <Result iconSize={150} sx={{py: 2}} />
                        )}
                    </Spin>
                </Scrollbar>
            </CardContent>
        </Card>
    );
};

const IconContainer = styled(Box)(({theme}) => ({
    display: "flex",
    alignItems: "center",
    justifyContent: "center",
    flexShrink: 0,
    borderRadius: "50%",
    backgroundColor: theme.palette.background.neutral,
    width: 35,
    height: 35
}));

const StyledIcon = styled(({color, ...props}) => {
    return <Icon {...props} />;
})(({theme, color}) => ({
    height: 20,
    width: 20,
    color: theme.palette[color].dark
}));

FeatureLimits.dimensions = {
    lg: {w: 6, h: 3, isResizable: false},
    md: {w: 4, h: 3, isResizable: false},
    sm: {w: 2, h: 3, isResizable: false},
    xs: {w: 1, h: 3, isResizable: false}
};

export default FeatureLimits;
