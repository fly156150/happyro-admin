export type CurrentUser = {
  passwordChangeEnabled: boolean;
  id?: number;
  userid?: string;
  username?: string;
  name?: string;
  avatar?: string;
  access?: string;
  roles?: string[];
  permissions?: string[];
};
