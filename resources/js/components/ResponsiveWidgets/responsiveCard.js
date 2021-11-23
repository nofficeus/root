import {Card} from "@mui/material";
import {experimentalStyled as styled} from "@mui/material/styles";

const ResponsiveCard = styled(Card)(() => ({
    ".react-grid-item &": {
        height: "100%",
        flexDirection: "column",
        display: "flex"
    }
}));

export default ResponsiveCard;
