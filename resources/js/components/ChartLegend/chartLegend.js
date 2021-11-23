import React from "react";
import {Box, Stack, Typography} from "@mui/material";
import {useTheme} from "@mui/material/styles";
import {defaultTo} from "lodash";

const ChartLegend = ({label, content, color, active = true}) => {
    const theme = useTheme();
    color = defaultTo(color, theme.palette.primary.main);

    return (
        <Stack
            direction="row"
            justifyContent="space-between"
            alignItems="center"
            spacing={2}>
            <Stack direction="row" alignItems="center" spacing={1}>
                <Box
                    sx={{
                        height: 16,
                        width: 16,
                        bgcolor: active ? color : "grey.50016",
                        borderRadius: 0.75
                    }}
                />
                <Typography sx={{color: "text.secondary"}} variant="body2">
                    {label}
                </Typography>
            </Stack>
            <Typography variant="subtitle2">{content}</Typography>
        </Stack>
    );
};

export default ChartLegend;
