import catalog from './job-group-catalog.json';
import { jobMappings } from './jobs';

export const jobGroups = [
  'first',
  'second',
  'third',
  'fourth',
  'novice',
  'expanded',
  'other',
] as const;
type JobGroup = (typeof jobGroups)[number];
const groups = catalog.groups as Record<string, JobGroup>;

export function jobGroup(id: number, traits?: boolean): JobGroup {
  return traits ? 'fourth' : (groups[id] ?? 'other');
}

export function groupedJobOptions(
  jobs: { id: number; traits?: boolean }[],
  translate: (id: string, fallback: string) => string,
) {
  return jobGroups
    .map((group) => ({
      label: translate(`players.job.group.${group}`, group),
      options: jobs
        .filter((job) => jobGroup(job.id, job.traits) === group)
        .sort((left, right) => left.id - right.id)
        .map((job) => ({
          value: job.id,
          label: translate(
            jobMappings[job.id] ?? 'players.job.unknown',
            `职业 ${job.id}`,
          ),
        })),
    }))
    .filter((group) => group.options.length > 0);
}
