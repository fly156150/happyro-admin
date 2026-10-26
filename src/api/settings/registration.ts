import { request } from '@umijs/max';

export type RegistrationSettings = {
  default_group_id: number;
  editable: boolean;
};

export function getRegistrationSettings() {
  return request<{ data: RegistrationSettings }>('/api/settings/registration');
}

export function updateRegistrationSettings(
  defaultGroupID: number,
  expectedGroupID: number,
) {
  return request<{ data: { default_group_id: number } }>(
    '/api/settings/registration',
    {
      method: 'PUT',
      data: {
        default_group_id: defaultGroupID,
        expected_group_id: expectedGroupID,
      },
    },
  );
}
