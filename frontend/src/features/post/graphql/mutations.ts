export const LIKE_POST = `
  mutation LikePost($postId: String!) {
    likePost(postId: $postId) {
      id
      likesCount
    }
  }
`

export const UNLIKE_POST = `
  mutation UnlikePost($postId: String!) {
    unlikePost(postId: $postId) {
      id
      likesCount
    }
  }
`