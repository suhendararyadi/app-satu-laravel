import type { Post, School } from '@/types/school';

interface Props {
    school: School;
    post: Post;
}

export default function PublicNewsShow({ post }: Props) {
    return (
        <div className="px-6 py-16">
            <div className="mx-auto max-w-4xl">
                {post.featured_image_path && (
                    <img
                        src={`/storage/${post.featured_image_path}`}
                        alt={post.title}
                        className="mb-8 h-72 w-full rounded-lg object-cover shadow-md"
                    />
                )}
                <h1 className="text-3xl font-bold text-gray-900">
                    {post.title}
                </h1>
                <div className="mt-3 flex items-center gap-3 text-sm text-gray-500">
                    <span>{post.author.name}</span>
                    {post.published_at && (
                        <>
                            <span>&middot;</span>
                            <span>
                                {new Date(post.published_at).toLocaleDateString(
                                    'id-ID',
                                    {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    },
                                )}
                            </span>
                        </>
                    )}
                </div>
                <div
                    className="prose prose-gray mt-8 max-w-none"
                    dangerouslySetInnerHTML={{ __html: post.content }}
                />
            </div>
        </div>
    );
}
